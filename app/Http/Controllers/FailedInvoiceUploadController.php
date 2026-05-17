<?php

namespace App\Http\Controllers;

use App\Models\FailedInvoiceUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FailedInvoiceUploadController extends Controller
{
    public function index(Request $request): View
    {
        return view('failed-invoices.index', [
            'failedInvoiceUploads' => FailedInvoiceUpload::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function download(Request $request, FailedInvoiceUpload $failedInvoiceUpload)
    {
        abort_unless($failedInvoiceUpload->user_id === $request->user()->id, 403);
        abort_unless(Storage::exists($failedInvoiceUpload->original_pdf_path), 404);

        return Storage::download(
            $failedInvoiceUpload->original_pdf_path,
            $failedInvoiceUpload->original_pdf_filename,
        );
    }

    public function destroy(Request $request, FailedInvoiceUpload $failedInvoiceUpload): RedirectResponse
    {
        abort_unless($failedInvoiceUpload->user_id === $request->user()->id, 403);

        Storage::delete($failedInvoiceUpload->original_pdf_path);
        $failedInvoiceUpload->delete();

        return redirect()
            ->route('failed-invoices.index')
            ->with('status', 'Mislukte upload is verwijderd.');
    }
}
