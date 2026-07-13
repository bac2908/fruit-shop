<?php

namespace Tests\Unit;

use App\Support\LocalDateTime;
use Carbon\Carbon;
use Tests\TestCase;

class LocalDateTimeTest extends TestCase
{
    public function test_it_displays_utc_timestamps_in_vietnam_time(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $utcTime = Carbon::create(2026, 7, 13, 13, 47, 0, 'UTC');

        $this->assertSame('13/07/2026 20:47', LocalDateTime::format($utcTime));
    }

    public function test_it_converts_a_vietnam_admin_input_to_utc_before_storage(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $utcTime = LocalDateTime::fromLocalInput('2026-07-13T20:47');

        $this->assertNotNull($utcTime);
        $this->assertSame('UTC', $utcTime->getTimezone()->getName());
        $this->assertSame('2026-07-13 13:47:00', $utcTime->format('Y-m-d H:i:s'));
    }
}
