<?php

namespace Tests\Feature;

use App\Services\DhlInvoiceParserService;
use App\Services\ReportPdfService;
use Tests\TestCase;

class ReportPdfServiceTest extends TestCase
{
    public function test_it_generates_pdf_for_selected_drivers(): void
    {
        $parsedData = app(DhlInvoiceParserService::class)->parse(base_path('test_file/13958-S-LM-2026-19 - kopie.pdf'));

        $pdf = app(ReportPdfService::class)->generate($parsedData, ['817893']);
        $selectedDrivers = app(ReportPdfService::class)->selectedDriversSummary($parsedData, ['817893']);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame([
            [
                'employee_number' => '817893',
                'name' => 'Mosaab El Fallouchi',
                'type' => 'STOPS',
                'hub_code' => 'BRE',
                'raw_type' => 'BREPAK',
            ],
        ], $selectedDrivers);
    }

    public function test_it_can_select_driver_by_type_and_employee_number(): void
    {
        $parsedData = app(DhlInvoiceParserService::class)->parse(base_path('test_file/13958-S-LM-2026-14 - kopie.pdf'));

        $selectedDrivers = app(ReportPdfService::class)->selectedDriversSummary($parsedData, ['HOURS_SPECIALIZED|824171']);

        $this->assertSame([
            [
                'employee_number' => '824171',
                'name' => 'Wilco Maas',
                'type' => 'HOURS_SPECIALIZED',
                'hub_code' => 'BRE',
                'raw_type' => 'BREPAK (Specialized)',
            ],
        ], $selectedDrivers);
    }

    public function test_it_detects_when_report_has_any_hours(): void
    {
        $service = app(ReportPdfService::class);

        $this->assertFalse($service->hasAnyHours([
            'ma_vr' => '00:00',
            'za' => '00:00',
            'zo' => '00:00',
        ]));

        $this->assertTrue($service->hasAnyHours([
            'ma_vr' => '00:00',
            'za' => '00:00',
            'zo' => '01:20',
        ]));
    }
}
