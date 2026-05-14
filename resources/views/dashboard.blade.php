@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col items-stretch gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Overzicht</p>
            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">DHL PDF rapporten</h1>
            <p class="mt-2 text-gray-600">Beheer facturen, rapporten en gebruikers.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
            <a href="{{ route('invoices.upload') }}" class="w-full rounded-md bg-yellow-500 px-4 py-2 text-center font-semibold text-gray-950 hover:bg-yellow-400 sm:w-auto">
                Factuur uploaden
            </a>
            <a href="{{ route('invoices.index') }}" class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-center font-semibold text-gray-800 hover:bg-gray-50 sm:w-auto">
                Facturen
            </a>
            <a href="{{ route('reports.index') }}" class="w-full rounded-md bg-gray-900 px-4 py-2 text-center font-semibold text-white hover:bg-gray-700 sm:w-auto">
                Rapporten
            </a>
            @if($user->isAdmin())
                <a href="{{ route('admin.users.create') }}" class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-center font-semibold text-gray-800 hover:bg-gray-50 sm:w-auto">
                    Gebruiker toevoegen
                </a>
            @endif
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
            <p class="text-sm text-gray-500">Geuploade facturen</p>
            <p class="mt-2 text-2xl font-semibold">{{ $invoiceUploadsCount }}</p>
        </section>
        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
            <p class="text-sm text-gray-500">Rapporten</p>
            <p class="mt-2 text-2xl font-semibold">{{ $reportsCount }}</p>
        </section>
        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
            <p class="text-sm text-gray-500">Rol</p>
            <p class="mt-2 text-2xl font-semibold">{{ $user->role }}</p>
        </section>
    </div>

    <section class="rounded-lg bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Laatste rapporten</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Week</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Jaar</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Bestandsnaam</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Chauffeurs</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Acties</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentReports as $report)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $report->week_number }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $report->year }}</td>
                            <td class="max-w-xs break-words px-6 py-4 text-sm text-gray-600">{{ $report->original_pdf_filename }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ count($report->selected_drivers ?? []) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('reports.download', $report) }}" title="Rapport downloaden" aria-label="Rapport downloaden" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-green-600 transition duration-200 hover:scale-110 hover:bg-green-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                                            <path d="M14 3v5h5" />
                                            <path d="M12 11v6" />
                                            <path d="m9 14 3 3 3-3" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Geen rapporten</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
