<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportPdfService
{
    public function __construct(
        private readonly HhMmTimeSumService $timeSumService = new HhMmTimeSumService,
    ) {}

    /**
     * @param  array<string, mixed>  $parsedData
     * @param  array<int, string>  $selectedEmployeeNumbers
     */
    public function generate(array $parsedData, array $selectedEmployeeNumbers): string
    {
        $drivers = collect($parsedData['drivers'] ?? [])
            ->filter(fn (array $driver): bool => $this->driverIsSelected($driver, $selectedEmployeeNumbers))
            ->values()
            ->all();
        $brepakDrivers = array_values(array_filter($drivers, fn (array $driver): bool => $this->isStopsDriver($driver)));
        $hourDrivers = array_values(array_filter($drivers, fn (array $driver): bool => ! $this->isStopsDriver($driver)));

        $grandStopTotals = $this->calculateGrandStopTotals($brepakDrivers);
        $grandTimeTotals = $this->calculateGrandTimeTotals($hourDrivers);

        return Pdf::loadView('reports.pdf', [
            'weekNumber' => $parsedData['week_number'],
            'year' => $parsedData['year'],
            'brepakDrivers' => $brepakDrivers,
            'hourDrivers' => $hourDrivers,
            'grandStopTotals' => $grandStopTotals,
            'grandTimeTotals' => $grandTimeTotals,
            'hasAnyHours' => $this->hasAnyHours($grandTimeTotals),
        ])
            ->setPaper('a4')
            ->output();
    }

    /**
     * @param  array{ma_vr: string, za: string, zo: string}  $hourTotals
     */
    public function hasAnyHours(array $hourTotals): bool
    {
        return $hourTotals['ma_vr'] !== '00:00'
            || $hourTotals['za'] !== '00:00'
            || $hourTotals['zo'] !== '00:00';
    }

    /**
     * @param  array<string, mixed>  $parsedData
     * @param  array<int, string>  $selectedEmployeeNumbers
     * @return array<int, array{employee_number: string, name: string, type: string, hub_code?: string, raw_type?: string}>
     */
    public function selectedDriversSummary(array $parsedData, array $selectedEmployeeNumbers): array
    {
        return collect($parsedData['drivers'] ?? [])
            ->filter(fn (array $driver): bool => $this->driverIsSelected($driver, $selectedEmployeeNumbers))
            ->map(fn (array $driver): array => [
                'employee_number' => $driver['employee_number'],
                'name' => $driver['name'],
                'type' => $driver['type'],
                'hub_code' => $driver['hub_code'] ?? null,
                'raw_type' => $driver['raw_type'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $driver
     * @param  array<int, string>  $selectedDriverKeys
     */
    private function driverIsSelected(array $driver, array $selectedDriverKeys): bool
    {
        return in_array($driver['type'].'|'.$driver['employee_number'], $selectedDriverKeys, true)
            || in_array($driver['employee_number'], $selectedDriverKeys, true);
    }

    /**
     * @param  array<string, mixed>  $driver
     */
    private function isStopsDriver(array $driver): bool
    {
        return ($driver['type'] ?? null) === 'STOPS'
            || ($driver['type'] ?? null) === 'BREPAK';
    }

    /**
     * @param  array<int, array<string, mixed>>  $drivers
     * @return array{ma_vr: int, za: int, zo: int, total: int}
     */
    private function calculateGrandStopTotals(array $drivers): array
    {
        $totals = [
            'ma_vr' => 0,
            'za' => 0,
            'zo' => 0,
            'total' => 0,
        ];

        foreach ($drivers as $driver) {
            $totals['ma_vr'] += $driver['totals']['ma_vr'];
            $totals['za'] += $driver['totals']['za'];
            $totals['zo'] += $driver['totals']['zo'];
            $totals['total'] += $driver['totals']['total'];
        }

        return $totals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $drivers
     * @return array{ma_vr: string, za: string, zo: string}
     */
    private function calculateGrandTimeTotals(array $drivers): array
    {
        return [
            'ma_vr' => $this->timeSumService->sum(array_column(array_column($drivers, 'totals'), 'ma_vr')),
            'za' => $this->timeSumService->sum(array_column(array_column($drivers, 'totals'), 'za')),
            'zo' => $this->timeSumService->sum(array_column(array_column($drivers, 'totals'), 'zo')),
        ];
    }
}
