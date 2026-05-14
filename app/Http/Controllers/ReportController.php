<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'reports' => Report::query()
                ->whereBelongsTo(request()->user())
                ->with('invoiceUpload')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function download(Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 403);
        abort_unless(Storage::exists($report->generated_pdf_path), 404);

        return Storage::download(
            $report->generated_pdf_path,
            'report-'.$report->year.'-week-'.$report->week_number.'.pdf',
        );
    }

    public function destroy(Report $report): RedirectResponse
    {
        abort_unless($report->user_id === auth()->id(), 403);

        Storage::delete($report->generated_pdf_path);
        $report->delete();

        return back()->with('status', 'Rapport is verwijderd.');
    }
}
