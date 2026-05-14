<?php

namespace App\Services;

use App\Exceptions\DhlInvoiceParserException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

class DhlInvoiceParserService
{
    public function __construct(
        private readonly Parser $parser = new Parser,
        private readonly HhMmTimeSumService $timeSumService = new HhMmTimeSumService,
    ) {}

    /**
     * @return array{
     *     week_number: int,
     *     year: int,
     *     drivers: array<int, array{
     *         hub_code: string,
     *         raw_type: string,
     *         type: string,
     *         name: string,
     *         employee_number: string,
     *         rows: array<int, array<string, mixed>>,
     *         totals: array<string, mixed>
     *     }>,
     *     grand_totals: array{ma_vr: int, za: int, zo: int, total: int},
     *     grand_time_totals: array{ma_vr: string, za: string, zo: string}
     * }
     */
    public function parse(string $pdfPath): array
    {
        $text = $this->extractText($pdfPath);
        $week = $this->parseWeek($text);
        $drivers = $this->parseDrivers($text);
        $stopDrivers = array_values(array_filter($drivers, fn (array $driver): bool => $driver['type'] === 'STOPS'));
        $hourDrivers = array_values(array_filter($drivers, fn (array $driver): bool => $driver['type'] !== 'STOPS'));

        $grandTotals = [
            'ma_vr' => array_sum(array_column(array_column($stopDrivers, 'totals'), 'ma_vr')),
            'za' => array_sum(array_column(array_column($stopDrivers, 'totals'), 'za')),
            'zo' => array_sum(array_column(array_column($stopDrivers, 'totals'), 'zo')),
            'total' => array_sum(array_column(array_column($stopDrivers, 'totals'), 'total')),
        ];
        $grandTimeTotals = [
            'ma_vr' => $this->timeSumService->sum(array_column(array_column($hourDrivers, 'totals'), 'ma_vr')),
            'za' => $this->timeSumService->sum(array_column(array_column($hourDrivers, 'totals'), 'za')),
            'zo' => $this->timeSumService->sum(array_column(array_column($hourDrivers, 'totals'), 'zo')),
        ];

        return [
            'week_number' => $week['week_number'],
            'year' => $week['year'],
            'drivers' => array_values($drivers),
            'grand_totals' => $grandTotals,
            'grand_time_totals' => $grandTimeTotals,
        ];
    }

    private function extractText(string $pdfPath): string
    {
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            $text = trim(implode("\n", array_map(
                fn ($page): string => $page->getText(),
                $pages,
            )));
        } catch (Throwable) {
            throw DhlInvoiceParserException::unreadablePdf();
        }

        if ($text === '') {
            throw DhlInvoiceParserException::unreadablePdf();
        }

        return $text;
    }

    /**
     * @return array{week_number: int, year: int}
     */
    private function parseWeek(string $text): array
    {
        if (! preg_match('/\bweek\s+(\d{1,2})\s*,\s*(\d{4})\b/i', $text, $matches)) {
            throw DhlInvoiceParserException::missingWeek();
        }

        return [
            'week_number' => (int) $matches[1],
            'year' => (int) $matches[2],
        ];
    }

    /**
     * @return array<int, array{hub_code: string, raw_type: string, type: string, name: string, employee_number: string, rows: array<int, array<string, mixed>>, totals: array<string, mixed>}>
     */
    private function parseDrivers(string $text): array
    {
        preg_match_all(
            '/Specificatie ritten gereden door VM - (?<driver>.+?) \((?<employee_number>\d+)\) op (?<raw_type>[A-Z]{3}(?:PAK(?:\s*\(Specialized\))?|ZON))(?<body>.*?)(?=Specificatie ritten gereden door VM -|Appendix: Ritgegevens|\z)/su',
            $text,
            $matches,
            PREG_SET_ORDER,
        );

        if ($matches === []) {
            throw DhlInvoiceParserException::missingDrivers();
        }

        $drivers = [];

        foreach ($matches as $match) {
            $invoiceType = $this->parseInvoiceType($match['raw_type']);

            Log::debug('DHL driver block detected', [
                'name' => $this->normalizeDriverName($match['driver']),
                'employee_number' => $match['employee_number'],
                'hub_code' => $invoiceType['hub_code'],
                'raw_type' => $invoiceType['raw_type'],
                'type' => $invoiceType['type'],
            ]);

            $rowsByDate = $invoiceType['type'] === 'STOPS'
                ? $this->parseBrepakRows($match['body'])
                : $this->parseHourRows($match['body'], $invoiceType['type']);

            if ($rowsByDate === []) {
                continue;
            }

            $rows = array_values($rowsByDate);
            usort($rows, fn (array $a, array $b): int => $this->dateKey($a['date']) <=> $this->dateKey($b['date']));

            $totals = $invoiceType['type'] === 'STOPS'
                ? $this->calculateBrepakTotals($rows)
                : $this->calculateHourTotals($rows);

            Log::debug('DHL parsed driver result', [
                'name' => $this->normalizeDriverName($match['driver']),
                'type' => $invoiceType['type'],
                'rows_count' => count($rows),
                'totals' => $totals,
            ]);

            $drivers[] = [
                'hub_code' => $invoiceType['hub_code'],
                'raw_type' => $invoiceType['raw_type'],
                'type' => $invoiceType['type'],
                'name' => $this->normalizeDriverName($match['driver']),
                'employee_number' => $match['employee_number'],
                'rows' => $rows,
                'totals' => $totals,
            ];
        }

        if ($drivers === []) {
            throw DhlInvoiceParserException::missingDrivers();
        }

        return $drivers;
    }

    /**
     * @return array<string, array{date: string, ma_vr: int, za: int, zo: int}>
     */
    private function parseBrepakRows(string $body): array
    {
        preg_match_all(
            '/(?<date>\d{2}-\d{2}-\d{4})\s+(?<postwijk>\d{4})\s+(?<planned>\d+)\s*\/\s*(?<success>\d+)/u',
            $body,
            $matches,
            PREG_SET_ORDER,
        );

        $rowsByDate = [];

        foreach ($matches as $match) {
            $date = $match['date'];
            $bucket = $this->bucketForDate($date);

            $rowsByDate[$date] ??= [
                'date' => $date,
                'ma_vr' => 0,
                'za' => 0,
                'zo' => 0,
            ];

            $rowsByDate[$date][$bucket] += (int) $match['success'];
        }

        return $rowsByDate;
    }

    /**
     * @return array<string, array{date: string, ma_vr: string, za: string, zo: string, uren: string, zondag_uren: string|null}>
     */
    private function parseHourRows(string $body, string $type): array
    {
        $rowsByDate = [];
        $lines = preg_split('/\R/u', $body) ?: [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));

            if (! preg_match('/^(?<date>\d{2}-\d{2}-\d{4})\b/u', $line, $dateMatch)) {
                continue;
            }

            preg_match_all('/\b\d{2}:\d{2}\b/u', $line, $timeMatches);
            $times = $timeMatches[0] ?? [];

            Log::debug('DHL hours line detected', [
                'line' => $line,
                'date' => $dateMatch['date'],
                'times' => $times,
            ]);

            if ($times === []) {
                continue;
            }

            $date = $dateMatch['date'];
            $bucket = $this->bucketForDate($date);
            $hours = $type === 'HOURS' && $bucket === 'zo' && isset($times[1])
                ? $times[1]
                : $times[0];

            $rowsByDate[$date] ??= [
                'date' => $date,
                'ma_vr' => '00:00',
                'za' => '00:00',
                'zo' => '00:00',
                'uren_ma_vr' => '00:00',
                'uren_za' => '00:00',
                'uren_zo' => '00:00',
                'uren' => $times[0],
                'zondag_uren' => $times[1] ?? null,
            ];

            $rowsByDate[$date][$bucket] = $this->timeSumService->add($rowsByDate[$date][$bucket], $hours);
            $rowsByDate[$date]['uren_'.$bucket] = $rowsByDate[$date][$bucket];
        }

        return $rowsByDate;
    }

    /**
     * @return array{hub_code: string, raw_type: string, type: string}
     */
    private function parseInvoiceType(string $rawType): array
    {
        $rawType = preg_replace('/\s+/u', ' ', trim($rawType));

        preg_match('/^(?<hub_code>[A-Z]{3})(?<suffix>PAK|ZON)(?<specialized>\s+\(Specialized\))?$/u', $rawType, $matches);

        $type = match (true) {
            isset($matches['specialized']) && trim($matches['specialized']) !== '' => 'HOURS_SPECIALIZED',
            ($matches['suffix'] ?? '') === 'ZON' => 'HOURS',
            default => 'STOPS',
        };

        return [
            'hub_code' => $matches['hub_code'] ?? '',
            'raw_type' => $rawType,
            'type' => $type,
        ];
    }

    /**
     * @param  array<int, array{date: string, ma_vr: int, za: int, zo: int}>  $rows
     * @return array{ma_vr: int, za: int, zo: int, total: int}
     */
    private function calculateBrepakTotals(array $rows): array
    {
        $totals = [
            'ma_vr' => array_sum(array_column($rows, 'ma_vr')),
            'za' => array_sum(array_column($rows, 'za')),
            'zo' => array_sum(array_column($rows, 'zo')),
        ];
        $totals['total'] = $totals['ma_vr'] + $totals['za'] + $totals['zo'];

        return $totals;
    }

    /**
     * @param  array<int, array{date: string, ma_vr: string, za: string, zo: string}>  $rows
     * @return array{ma_vr: string, za: string, zo: string}
     */
    private function calculateHourTotals(array $rows): array
    {
        return [
            'ma_vr' => $this->timeSumService->sum(array_column($rows, 'ma_vr')),
            'za' => $this->timeSumService->sum(array_column($rows, 'za')),
            'zo' => $this->timeSumService->sum(array_column($rows, 'zo')),
        ];
    }

    private function bucketForDate(string $date): string
    {
        $dayOfWeek = CarbonImmutable::createFromFormat('d-m-Y', $date)->dayOfWeekIso;

        return match ($dayOfWeek) {
            6 => 'za',
            7 => 'zo',
            default => 'ma_vr',
        };
    }

    private function normalizeDriverName(string $rawName): string
    {
        $parts = array_map('trim', explode(',', $rawName, 2));

        if (count($parts) === 2) {
            return $this->titleName($parts[1].' '.$parts[0]);
        }

        return $this->titleName($rawName);
    }

    private function titleName(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim(preg_replace('/\s+/', ' ', $value))), MB_CASE_TITLE, 'UTF-8');
    }

    private function dateKey(string $date): int
    {
        return CarbonImmutable::createFromFormat('d-m-Y', $date)->getTimestamp();
    }
}
