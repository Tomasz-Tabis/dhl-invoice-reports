@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Facturen</p>
        <h1 class="mt-1 text-3xl font-semibold">Factuur uploaden</h1>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" class="max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="invoice_pdf" class="block text-sm font-medium text-gray-700">PDF-bestand</label>
            <input id="invoice_pdf" name="invoice_pdf" type="file" accept="application/pdf,.pdf" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 file:mr-4 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700">
            <p class="mt-1 text-xs text-gray-500">Alleen PDF, maximaal 20 MB.</p>
            @error('invoice_pdf')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if($errors->any())
            <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Controleer het formulier en probeer het opnieuw.
            </div>
        @endif

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-yellow-500 px-4 py-2 font-semibold text-gray-950 hover:bg-yellow-400">
                Uploaden
            </button>
            <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-950">Annuleren</a>
        </div>
    </form>
@endsection
