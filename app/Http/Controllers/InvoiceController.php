<?php

namespace App\Http\Controllers;

use App\Exceptions\DhlInvoiceParserException;
use App\Models\InvoiceUpload;
use App\Models\Report;
use App\Services\DhlInvoiceParserService;
use App\Services\ReportPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
        $originalPath = $file->storeAs('invoices', $storedFilename);

        try {
            $parsedData = $parser->parse(Storage::path($originalPath));
        } catch (DhlInvoiceParserException $exception) {
            Storage::delete($originalPath);

            return back()
                ->withErrors(['invoice_pdf' => $exception->getMessage()])
                ->withInput();
        }

        $invoiceUpload = InvoiceUpload::create([
            'user_id' => $request->user()->id,
            'original_pdf_path' => $originalPath,
            'original_pdf_filename' => $file->getClientOriginalName(),
            'parsed_data' => $parsedData,
            'week_number' => $parsedData['week_number'],
            'year' => $parsedData['year'],
        ]);

        return redirect()
            ->route('invoices.show', $invoiceUpload)
            ->with('status', 'Factuur is geupload. Je kunt nu een rapport genereren.');
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
            'selected_drivers.*' => ['required', 'string', 'in:'.implode(',', $availableDriverKeys)],
        ], [
            'selected_drivers.required' => 'Selecteer minimaal één chauffeur.',
            'selected_drivers.min' => 'Selecteer minimaal één chauffeur.',
            'selected_drivers.*.in' => 'Selecteer een geldige chauffeur.',
        ]);

        $selectedDriversSummary = $reportPdfService->selectedDriversSummary(
            $invoiceUpload->parsed_data,
            $validated['selected_drivers'],
        );

        $generatedReportPath = 'reports/report-'.$invoiceUpload->id.'-'.Str::uuid().'.pdf';

        Storage::put($generatedReportPath, $reportPdfService->generate(
            $invoiceUpload->parsed_data,
            $validated['selected_drivers'],
        ));

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
