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
     *         company: string,
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
     * @return array<int, array{company: string, hub_code: string, raw_type: string, type: string, name: string, employee_number: string, rows: array<int, array<string, mixed>>, totals: array<string, mixed>}>
     */
    private function parseDrivers(string $text): array
    {
        [$matches, $format] = $this->matchDriverBlocks($text);

        if ($matches === []) {
            throw DhlInvoiceParserException::missingDrivers();
        }

        Log::debug('Detected DHL header format', [
            'format' => $format,
        ]);

        $drivers = [];
        $skippedIgnoredBlock = false;

        foreach ($matches as $match) {
            $company = trim(preg_replace('/\s+/u', ' ', $match['company']));
            $driver = trim(preg_replace('/\s+/u', ' ', $match['driver']));
            $rawType = preg_replace('/\s+/u', ' ', trim($match['raw_type']));
            $name = $this->normalizeDriverName($driver);
            $employeeNumber = $match['employee_number'];

            Log::debug('DHL specification block detected', [
                'company' => $company,
                'driver' => $driver,
                'employee_number' => $employeeNumber,
                'raw_type' => $rawType,
            ]);

            $invoiceType = $this->parseInvoiceType($rawType);

            Log::debug('DHL normalized type detected', [
                'raw_type' => $invoiceType['raw_type'],
                'hub_code' => $invoiceType['hub_code'],
                'normalized_type' => $invoiceType['type'],
            ]);

            if ($invoiceType['type'] === 'IGNORE' || $this->containsNcc($match['body'])) {
                $skippedIgnoredBlock = true;

                Log::debug('DHL NCC block skipped', [
                    'raw_type' => $invoiceType['raw_type'],
                    'driver' => $name ?? null,
                ]);

                continue;
            }

            Log::debug('DHL driver block detected', [
                'company' => $company,
                'name' => $name,
                'employee_number' => $employeeNumber,
                'hub_code' => $invoiceType['hub_code'],
                'raw_type' => $invoiceType['raw_type'],
                'type' => $invoiceType['type'],
            ]);

            $rowsByDate = $invoiceType['type'] === 'STOPS'
                ? $this->parseStopRows($match['body'])
                : $this->parseHourRows($match['body'], $invoiceType['type']);

            if ($rowsByDate === []) {
                continue;
            }

            $rows = array_values($rowsByDate);
            usort($rows, fn (array $a, array $b): int => $this->dateKey($a['date']) <=> $this->dateKey($b['date']));

            $totals = $invoiceType['type'] === 'STOPS'
                ? $this->calculateStopTotals($rows)
                : $this->calculateHourTotals($rows);

            Log::debug('DHL parsed driver result', [
                'name' => $name,
                'type' => $invoiceType['type'],
                'rows_count' => count($rows),
                'totals' => $totals,
            ]);

            $drivers[] = [
                'company' => $company,
                'hub_code' => $invoiceType['hub_code'],
                'raw_type' => $invoiceType['raw_type'],
                'type' => $invoiceType['type'],
                'name' => $name,
                'employee_number' => $employeeNumber,
                'rows' => $rows,
                'totals' => $totals,
            ];
        }

        if ($drivers === [] && ! $skippedIgnoredBlock) {
            throw DhlInvoiceParserException::missingDrivers();
        }

        return $drivers;
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: string|null}
     */
    private function matchDriverBlocks(string $text): array
    {
        $rawTypePattern = '[A-Z]{3}(?:PAK(?:\s*\(Specialized\))?|ZON)|[^\r\n]*\bNCC\b[^\r\n]*';

        $patterns = [
            'A' => '/Specificatie ritten gereden door\s+(?<company>.+?)\s+-\s+(?<driver>.+?)\s+\((?<employee_number>\d+)\)\s+op\s+(?<raw_type>'.$rawTypePattern.')(?<body>.*?)(?=Specificatie ritten gereden door\s+.+?\s+-|Appendix: Ritgegevens|\z)/su',
            'B' => '/Specificatie\s+(?<raw_type>'.$rawTypePattern.')\s+door\s+(?<company>.+?)\s+-\s+(?<driver>.+?)\s+\((?<employee_number>\d+)\)(?<body>.*?)(?=Specificatie\s+(?:'.$rawTypePattern.')\s+door\s+.+?\s+-|Appendix: Ritgegevens|\z)/su',
        ];

        foreach ($patterns as $format => $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

            if ($matches !== []) {
                return [$matches, $format];
            }
        }

        return [[], null];
    }

    /**
     * @return array<string, array{date: string, ma_vr: int, za: int, zo: int}>
     */
    private function parseStopRows(string $body): array
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

        if ($this->containsNcc($rawType)) {
            return [
                'hub_code' => '',
                'raw_type' => $rawType,
                'type' => 'IGNORE',
            ];
        }

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
    private function calculateStopTotals(array $rows): array
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

    private function containsNcc(string $value): bool
    {
        return preg_match('/\bNCC\b/iu', $value) === 1;
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
