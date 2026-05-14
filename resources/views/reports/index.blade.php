@extends('layouts.app')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Rapporten</p>
            <h1 class="mt-1 text-3xl font-semibold">Mijn rapporten</h1>
        </div>

        <a href="{{ route('invoices.index') }}" class="rounded-md bg-yellow-500 px-4 py-2 font-semibold text-gray-950 hover:bg-yellow-400">
            Facturen
        </a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Factuur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Week</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Jaar</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Chauffeurs</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Aangemaakt</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($reports as $report)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium">{{ $report->original_pdf_filename }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $report->week_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $report->year }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ count($report->selected_drivers ?? []) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $report->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex justify-end gap-2">
                                @if($report->invoiceUpload)
                                    <a href="{{ route('invoices.show', $report->invoiceUpload) }}" title="Factuur openen" aria-label="Factuur openen" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 transition duration-200 hover:scale-110 hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v7A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z" />
                                            <path d="M3 10h18" />
                                        </svg>
                                    </a>
                                @endif
                                <a href="{{ route('reports.download', $report) }}" title="Rapport downloaden" aria-label="Rapport downloaden" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-green-600 transition duration-200 hover:scale-110 hover:bg-green-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                                        <path d="M14 3v5h5" />
                                        <path d="M12 11v6" />
                                        <path d="m9 14 3 3 3-3" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('reports.destroy', $report) }}" class="inline" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Rapport verwijderen" aria-label="Rapport verwijderen" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:scale-110 hover:bg-red-100">
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
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Geen rapporten</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $reports->links() }}
    </div>
@endsection
