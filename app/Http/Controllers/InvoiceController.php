<?php

namespace App\Http\Controllers;

use App\Models\FailedInvoiceUpload;
use App\Models\InvoiceUpload;
use App\Models\Report;
use App\Services\DhlInvoiceParserService;
use App\Services\ReportPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index', [
            'invoiceUploads' => InvoiceUpload::query()
                ->where('user_id', auth()->id())
                ->withCount('reports')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function uploadForm(): View
    {
        return view('invoices.upload');
    }

    public function store(Request $request, DhlInvoiceParserService $parser): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'invoice_pdf.required' => 'Selecteer een PDF-bestand.',
            'invoice_pdf.file' => 'Selecteer een geldig bestand.',
            'invoice_pdf.mimes' => 'Alleen PDF-bestanden zijn toegestaan.',
            'invoice_pdf.max' => 'Het PDF-bestand mag maximaal 20 MB zijn.',
        ]);

        $file = $validated['invoice_pdf'];
        $storedFilename = now()->format('YmdHis').'-'.Str::uuid().'.pdf';

        try {
            $parsedData = $parser->parse($file->getRealPath());
        } catch (Throwable $exception) {
            try {
                $failedPath = $file->storeAs('failed', $storedFilename);

                if ($failedPath === false) {
                    throw new \RuntimeException('Failed upload storage returned false.');
                }

                FailedInvoiceUpload::create([
                    'user_id' => $request->user()->id,
                    'original_pdf_path' => $failedPath,
                    'original_pdf_filename' => $file->getClientOriginalName(),
                    'error_message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                return back()
                    ->withErrors(['invoice_pdf' => 'De factuur kon niet worden verwerkt en niet worden opgeslagen bij mislukte uploads.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['invoice_pdf' => 'De factuur kon niet worden verwerkt, maar het bestand is opgeslagen in mislukte uploads.'])
                ->withInput();
        }

        try {
            $originalPath = $file->storeAs('invoices', $storedFilename);

            if ($originalPath === false) {
                throw new \RuntimeException('Invoice storage returned false.');
            }

            $invoiceUpload = InvoiceUpload::create([
                'user_id' => $request->user()->id,
                'original_pdf_path' => $originalPath,
                'original_pdf_filename' => $file->getClientOriginalName(),
                'parsed_data' => $parsedData,
                'week_number' => $parsedData['week_number'],
                'year' => $parsedData['year'],
            ]);
        } catch (Throwable) {
            if (isset($originalPath) && $originalPath !== false) {
                Storage::delete($originalPath);
            }

            return back()
                ->withErrors(['invoice_pdf' => 'De factuur kon niet worden opgeslagen. Probeer het opnieuw.'])
                ->withInput();
        }

        return redirect()
            ->route('invoices.show', $invoiceUpload)
            ->with('status', 'Factuur is geüpload. Je kunt nu een rapport genereren.');
    }

    public function show(Request $request, InvoiceUpload $invoiceUpload): View
    {
        abort_unless($invoiceUpload->user_id === $request->user()->id, 403);

        return view('invoices.show', [
            'invoiceUpload' => $invoiceUpload,
            'drivers' => $invoiceUpload->parsed_data['drivers'] ?? [],
            'reports' => $invoiceUpload->reports()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get(),
        ]);
    }

    public function generateSelectedReport(Request $request, InvoiceUpload $invoiceUpload, ReportPdfService $reportPdfService): RedirectResponse
    {
        abort_unless($invoiceUpload->user_id === $request->user()->id, 403);

        $drivers = collect($invoiceUpload->parsed_data['drivers'] ?? []);
        $availableDriverKeys = $drivers
            ->map(fn (array $driver): string => $driver['type'].'|'.$driver['employee_number'])
            ->all();

        $validated = $request->validate([
            'selected_drivers' => ['required', 'array', 'min:1'],
            'selected_drivers.*' => ['required', 'string', Rule::in($availableDriverKeys)],
        ], [
            'selected_drivers.required' => 'Selecteer minimaal één chauffeur.',
            'selected_drivers.min' => 'Selecteer minimaal één chauffeur.',
            'selected_drivers.*.in' => 'Selecteer een geldige chauffeur.',
        ]);

        $generatedReportPath = 'reports/report-'.$invoiceUpload->id.'-'.Str::uuid().'.pdf';

        try {
            $selectedDriversSummary = $reportPdfService->selectedDriversSummary(
                $invoiceUpload->parsed_data,
                $validated['selected_drivers'],
            );

            $pdfContents = $reportPdfService->generate(
                $invoiceUpload->parsed_data,
                $validated['selected_drivers'],
            );

            if (Storage::put($generatedReportPath, $pdfContents) === false) {
                throw new \RuntimeException('Report storage returned false.');
            }

            Report::create([
                'user_id' => $request->user()->id,
                'invoice_upload_id' => $invoiceUpload->id,
                'original_pdf_path' => $invoiceUpload->original_pdf_path,
                'original_pdf_filename' => $invoiceUpload->original_pdf_filename,
                'generated_pdf_path' => $generatedReportPath,
                'week_number' => $invoiceUpload->week_number,
                'year' => $invoiceUpload->year,
                'selected_drivers' => $selectedDriversSummary,
            ]);
        } catch (Throwable) {
            Storage::delete($generatedReportPath);

            return back()
                ->withErrors(['selected_drivers' => 'Het rapport kon niet worden gegenereerd. Probeer het opnieuw.'])
                ->withInput();
        }

        return redirect()
            ->route('invoices.show', $invoiceUpload)
            ->with('status', 'Rapport is gegenereerd.');
    }

    public function download(Request $request, InvoiceUpload $invoiceUpload)
    {
        abort_unless($invoiceUpload->user_id === $request->user()->id, 403);
        abort_unless(Storage::exists($invoiceUpload->original_pdf_path), 404);

        return Storage::download(
            $invoiceUpload->original_pdf_path,
            $invoiceUpload->original_pdf_filename,
        );
    }

    public function destroy(Request $request, InvoiceUpload $invoiceUpload): RedirectResponse
    {
        abort_unless($invoiceUpload->user_id === $request->user()->id, 403);

        $reports = $invoiceUpload->reports()->get();

        foreach ($reports as $report) {
            Storage::delete($report->generated_pdf_path);
            $report->delete();
        }

        Storage::delete($invoiceUpload->original_pdf_path);
        $invoiceUpload->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', 'Factuur is verwijderd.');
    }
}
