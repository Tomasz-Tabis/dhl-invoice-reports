<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FailedInvoiceUploadController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/upload', [InvoiceController::class, 'uploadForm'])->name('invoices.upload');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoiceUpload}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoiceUpload}/reports', [InvoiceController::class, 'generateSelectedReport'])->name('invoices.reports.store');
    Route::get('/invoices/{invoiceUpload}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::delete('/invoices/{invoiceUpload}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    Route::get('/failed-invoices', [FailedInvoiceUploadController::class, 'index'])->name('failed-invoices.index');
    Route::get('/failed-invoices/{failedInvoiceUpload}/download', [FailedInvoiceUploadController::class, 'download'])->name('failed-invoices.download');
    Route::delete('/failed-invoices/{failedInvoiceUpload}', [FailedInvoiceUploadController::class, 'destroy'])->name('failed-invoices.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'destroy']);
    });
});
