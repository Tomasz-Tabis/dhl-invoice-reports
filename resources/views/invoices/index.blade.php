@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Facturen</p>
            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">Opgeslagen facturen</h1>
        </div>

        <a href="{{ route('invoices.upload') }}" class="w-full rounded-md bg-yellow-500 px-4 py-2 text-center font-semibold text-gray-950 hover:bg-yellow-400 sm:w-auto">
            Factuur uploaden
        </a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Bestandsnaam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Week</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Jaar</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Uploaddatum</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Aantal rapporten</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($invoiceUploads as $invoiceUpload)
                    <tr>
                        <td class="max-w-xs break-words px-6 py-4 text-sm font-medium">{{ $invoiceUpload->original_pdf_filename }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $invoiceUpload->week_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $invoiceUpload->year }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $invoiceUpload->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $invoiceUpload->reports_count }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('invoices.show', $invoiceUpload) }}" title="Factuur openen" aria-label="Factuur openen" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 transition duration-200 hover:scale-110 hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v7A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z" />
                                        <path d="M3 10h18" />
                                    </svg>
                                </a>
                                <a href="{{ route('invoices.download', $invoiceUpload) }}" title="Originele PDF downloaden" aria-label="Originele PDF downloaden" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-green-600 transition duration-200 hover:scale-110 hover:bg-green-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3v12" />
                                        <path d="m7 10 5 5 5-5" />
                                        <path d="M5 21h14" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('invoices.destroy', $invoiceUpload) }}" class="inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Factuur verwijderen" aria-label="Factuur verwijderen" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:scale-110 hover:bg-red-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 7h16" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M6 7l1 14h10l1-14" />
                                        <path d="M9 7V4h6v3" />
                                    </svg>
                                </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Geen gegevens</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $invoiceUploads->links() }}
    </div>
@endsection
