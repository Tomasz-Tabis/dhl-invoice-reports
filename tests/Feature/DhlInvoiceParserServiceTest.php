<?php

namespace Tests\Feature;

use App\Services\DhlInvoiceParserService;
use Tests\TestCase;

class DhlInvoiceParserServiceTest extends TestCase
{
    public function test_it_parses_dhl_invoice_pdf(): void
    {
        $data = app(DhlInvoiceParserService::class)->parse(base_path('test_file/13958-S-LM-2026-19 - kopie.pdf'));

        $this->assertSame(19, $data['week_number']);
        $this->assertSame(2026, $data['year']);

        $driver = collect($data['drivers'])->firstWhere('employee_number', '817893');
        $brezonDriver = collect($data['drivers'])->firstWhere('employee_number', '824171');

        $this->assertSame('BRE', $driver['hub_code']);
        $this->assertSame('BREPAK', $driver['raw_type']);
        $this->assertSame('STOPS', $driver['type']);
        $this->assertSame('Mosaab El Fallouchi', $driver['name']);
        $this->assertSame([
            'date' => '05-05-2026',
            'ma_vr' => 207,
            'za' => 0,
            'zo' => 0,
        ], $driver['rows'][0]);
        $this->assertSame([
            'date' => '09-05-2026',
            'ma_vr' => 0,
            'za' => 160,
            'zo' => 0,
        ], $driver['rows'][4]);
        $this->assertSame([
            'ma_vr' => 730,
            'za' => 160,
            'zo' => 0,
            'total' => 890,
        ], $driver['totals']);

        $this->assertSame('BRE', $brezonDriver['hub_code']);
        $this->assertSame('BREZON', $brezonDriver['raw_type']);
        $this->assertSame('HOURS', $brezonDriver['type']);
        $this->assertSame('Wilco Maas', $brezonDriver['name']);
        $this->assertSame([
            'date' => '10-05-2026',
            'ma_vr' => '00:00',
            'za' => '00:00',
            'zo' => '03:16',
            'uren_ma_vr' => '00:00',
            'uren_za' => '00:00',
            'uren_zo' => '03:16',
            'uren' => '03:16',
            'zondag_uren' => '03:16',
        ], $brezonDriver['rows'][0]);
        $this->assertSame([
            'ma_vr' => '00:00',
            'za' => '00:00',
            'zo' => '03:16',
        ], $brezonDriver['totals']);
        $this->assertSame([
            'ma_vr' => 4067,
            'za' => 671,
            'zo' => 0,
            'total' => 4738,
        ], $data['grand_totals']);
        $this->assertSame([
            'ma_vr' => '00:00',
            'za' => '00:00',
            'zo' => '03:16',
        ], $data['grand_time_totals']);
    }

    public function test_it_parses_brepak_specialized_as_hours(): void
    {
        $service = app(DhlInvoiceParserService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseDrivers');
        $method->setAccessible(true);

        $drivers = $method->invoke($service, implode("\n", [
            'Specificatie ritten gereden door VM - MAAS, WILCO (824171) op BREPAK (Specialized)',
            'Datum uren p/u (€ ) bedrag (€ )',
            '30-03-2026 01:20 33,53 44,71',
            'Appendix: Ritgegevens',
            '30-03-2026 824171 PAK 1000 1 0',
        ]));

        $this->assertCount(1, $drivers);
        $this->assertSame('BRE', $drivers[0]['hub_code']);
        $this->assertSame('BREPAK (Specialized)', $drivers[0]['raw_type']);
        $this->assertSame('HOURS_SPECIALIZED', $drivers[0]['type']);
        $this->assertSame('Wilco Maas', $drivers[0]['name']);
        $this->assertSame([
            'date' => '30-03-2026',
            'ma_vr' => '01:20',
            'za' => '00:00',
            'zo' => '00:00',
            'uren_ma_vr' => '01:20',
            'uren_za' => '00:00',
            'uren_zo' => '00:00',
            'uren' => '01:20',
            'zondag_uren' => null,
        ], $drivers[0]['rows'][0]);
        $this->assertSame([
            'ma_vr' => '01:20',
            'za' => '00:00',
            'zo' => '00:00',
        ], $drivers[0]['totals']);
    }

    public function test_it_detects_dynamic_dhl_locations(): void
    {
        $service = app(DhlInvoiceParserService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseDrivers');
        $method->setAccessible(true);

        $drivers = $method->invoke($service, implode("\n", [
            'Specificatie ritten gereden door VM - DRIVER, ONE (111111) op AMSPAK',
            'Datum Postwijk Stops plan/succes',
            '01-04-2026 1000 220 / 207',
            'Specificatie ritten gereden door VM - DRIVER, TWO (222222) op RTMZON',
            'Datum uren p/u (€ ) zondag uren zon/feest p/u (€ ) bedrag (€ )',
            '05-04-2026 03:16 35,16 03:16 15,24 164,64',
            'Specificatie ritten gereden door VM - DRIVER, THREE (333333) op UTRPAK (Specialized)',
            'Datum uren p/u (€ ) bedrag (€ )',
            '06-04-2026 01:20 33,53 44,71',
            'Appendix: Ritgegevens',
        ]));

        $this->assertSame([
            'hub_code' => 'AMS',
            'raw_type' => 'AMSPAK',
            'type' => 'STOPS',
        ], array_intersect_key($drivers[0], array_flip(['hub_code', 'raw_type', 'type'])));

        $this->assertSame([
            'hub_code' => 'RTM',
            'raw_type' => 'RTMZON',
            'type' => 'HOURS',
        ], array_intersect_key($drivers[1], array_flip(['hub_code', 'raw_type', 'type'])));

        $this->assertSame([
            'hub_code' => 'UTR',
            'raw_type' => 'UTRPAK (Specialized)',
            'type' => 'HOURS_SPECIALIZED',
        ], array_intersect_key($drivers[2], array_flip(['hub_code', 'raw_type', 'type'])));
    }
}
