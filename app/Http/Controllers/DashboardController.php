<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'invoiceUploadsCount' => $user->invoiceUploads()->count(),
            'failedInvoiceUploadsCount' => $user->failedInvoiceUploads()->count(),
            'reportsCount' => $user->reports()->count(),
            'recentReports' => $user->reports()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
