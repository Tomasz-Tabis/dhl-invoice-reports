<?php

namespace Tests\Unit;

use App\Services\HhMmTimeSumService;
use PHPUnit\Framework\TestCase;

class HhMmTimeSumServiceTest extends TestCase
{
    public function test_it_sums_hh_mm_times(): void
    {
        $service = new HhMmTimeSumService();

        $this->assertSame('06:01', $service->sum(['03:16', '02:45']));
        $this->assertSame('04:36', $service->sum(['01:20', '03:16']));
        $this->assertSame('10:00', $service->add('09:30', '00:30'));
    }
}
