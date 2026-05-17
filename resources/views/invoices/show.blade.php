@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col items-stretch gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Factuur</p>
            <h1 class="mt-1 break-words text-2xl font-semibold sm:text-3xl">{{ $invoiceUpload->original_pdf_filename }}</h1>
            <p class="mt-2 text-sm text-gray-600">Week {{ $invoiceUpload->week_number }}, {{ $invoiceUpload->year }}</p>
        </div>

        <a href="{{ route('invoices.download', $invoiceUpload) }}" title="Originele PDF downloaden" aria-label="Originele PDF downloaden" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-green-600 transition duration-200 hover:scale-110 hover:bg-green-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3v12" />
                <path d="m7 10 5 5 5-5" />
                <path d="M5 21h14" />
            </svg>
        </a>
    </div>

    @error('selected_drivers')
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @php
        $isStopsDriver = fn ($driver) => ($driver['type'] ?? null) === 'STOPS';
        $isIgnoredDriver = fn ($driver) => ($driver['type'] ?? null) === 'IGNORE'
            || str_contains(strtoupper($driver['raw_type'] ?? ''), 'NCC')
            || str_contains(strtoupper($driver['type'] ?? ''), 'NCC');
        $visibleDrivers = collect($drivers)->reject($isIgnoredDriver)->values();
        $stopDrivers = $visibleDrivers->filter($isStopsDriver)->values();
        $hourDrivers = $visibleDrivers->reject($isStopsDriver)->values();
    @endphp

    <form method="POST" action="{{ route('invoices.reports.store', $invoiceUpload) }}">
        @csrf

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <button type="button" id="select-all" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 sm:w-auto">Alles selecteren</button>
            <button type="button" id="deselect-all" class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 sm:w-auto">Alles deselecteren</button>
            <button type="submit" class="w-full rounded-md bg-yellow-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-400 sm:w-auto">Rapport genereren</button>
        </div>

        <div class="mb-8 overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold">Stops chauffeurs</h2>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-12 px-6 py-3"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Chauffeur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Hub</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nummer</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Stops ma/vr</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Stops za</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Stops zo</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Totaal stops</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stopDrivers as $driver)
                        @php($driverKey = $driver['type'].'|'.$driver['employee_number'])
                        <tr>
                            <td class="px-6 py-4">
                                <input type="checkbox" name="selected_drivers[]" value="{{ $driverKey }}" class="driver-checkbox h-5 w-5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500" @checked(in_array($driverKey, old('selected_drivers', []), true))>
                            </td>
                            <td class="max-w-xs break-words px-6 py-4 text-sm font-medium">{{ $driver['name'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['hub_code'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['raw_type'] ?? $driver['type'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['employee_number'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['ma_vr'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['za'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['zo'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold">{{ $driver['totals']['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Geen gegevens</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mb-8 overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold">Uren chauffeurs</h2>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-12 px-6 py-3"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Chauffeur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Hub</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nummer</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Uren ma/vr</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Uren za</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Uren zo</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Totaal uren</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($hourDrivers as $driver)
                        @php($driverKey = $driver['type'].'|'.$driver['employee_number'])
                        <tr>
                            <td class="px-6 py-4">
                                <input type="checkbox" name="selected_drivers[]" value="{{ $driverKey }}" class="driver-checkbox h-5 w-5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500" @checked(in_array($driverKey, old('selected_drivers', []), true))>
                            </td>
                            <td class="max-w-xs break-words px-6 py-4 text-sm font-medium">{{ $driver['name'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['hub_code'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['raw_type'] ?? $driver['type'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $driver['employee_number'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['ma_vr'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['za'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">{{ $driver['totals']['zo'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold">
                                {{ app(\App\Services\HhMmTimeSumService::class)->sum([$driver['totals']['ma_vr'], $driver['totals']['za'], $driver['totals']['zo']]) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Geen gegevens</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Rapporten uit deze factuur</h2>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Gegenereerd op</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Chauffeurs</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $report->created_at?->format('Y-m-d H:i') }}</td>
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
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">Geen rapporten</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>

    <script>
        const checkboxes = document.querySelectorAll('.driver-checkbox');
        document.getElementById('select-all')?.addEventListener('click', () => checkboxes.forEach((checkbox) => checkbox.checked = true));
        document.getElementById('deselect-all')?.addEventListener('click', () => checkboxes.forEach((checkbox) => checkbox.checked = false));
    </script>
@endsection
