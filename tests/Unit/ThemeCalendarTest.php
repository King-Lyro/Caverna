<?php

namespace Tests\Unit;

use App\Models\SiteThemeSchedule;
use App\Services\ThemeCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThemeCalendarTest extends TestCase
{
    #[DataProvider('ranges')]
    public function test_overlap_checks_are_symmetric_and_calendar_accurate(array $left, array $right, bool $overlap): void
    {
        $calendar = new ThemeCalendar;
        $a = new SiteThemeSchedule($left);
        $b = new SiteThemeSchedule($right);
        $this->assertSame($overlap, $calendar->overlaps($a, $b));
        $this->assertSame($overlap, $calendar->overlaps($b, $a));
    }

    public static function ranges(): array
    {
        $annual = fn ($start, $end) => ['recurrence' => 'annual', 'starts_md' => $start, 'ends_md' => $end];
        $once = fn ($start, $end) => ['recurrence' => 'once', 'starts_on' => $start, 'ends_on' => $end];

        return [
            'same day' => [$once('2026-10-31', '2026-10-31'), $once('2026-10-31', '2026-10-31'), true],
            'adjacent days' => [$once('2026-10-31', '2026-10-31'), $once('2026-11-01', '2026-11-01'), false],
            'inclusive end' => [$once('2026-10-01', '2026-10-31'), $once('2026-10-31', '2026-11-01'), true],
            'new year intersection' => [$annual('12-20', '01-05'), $annual('01-05', '01-10'), true],
            'new year gap' => [$annual('12-20', '01-05'), $annual('01-06', '12-19'), false],
            'leap clamp collision' => [$annual('02-29', '02-29'), $annual('02-28', '02-28'), true],
            'leap no collision' => [$annual('02-29', '02-29'), $annual('03-01', '03-01'), false],
            'future non-leap century' => [$annual('02-29', '02-29'), $once('2100-02-28', '2100-02-28'), true],
            'future leap century gap' => [$annual('02-29', '02-29'), $once('2400-02-28', '2400-02-28'), false],
            'future leap century match' => [$annual('02-29', '02-29'), $once('2400-02-29', '2400-02-29'), true],
            'annual inside long range' => [$annual('07-01', '07-02'), $once('2080-09-01', '2082-03-01'), true],
            'previous year occurrence' => [$annual('12-20', '01-05'), $once('2080-01-01', '2080-01-02'), true],
        ];
    }

    public function test_calendar_days_follow_timezone_and_daylight_saving_boundaries(): void
    {
        config(['app.timezone' => 'America/New_York']);
        $calendar = new ThemeCalendar;
        $schedule = new SiteThemeSchedule(['recurrence' => 'once', 'starts_on' => '2026-03-08', 'ends_on' => '2026-03-08']);
        $this->assertFalse($calendar->matches($schedule, CarbonImmutable::parse('2026-03-08 04:59:59', 'UTC')));
        $this->assertTrue($calendar->matches($schedule, CarbonImmutable::parse('2026-03-08 05:00:00', 'UTC')));
        $this->assertTrue($calendar->matches($schedule, CarbonImmutable::parse('2026-03-09 03:59:59', 'UTC')));
        $this->assertFalse($calendar->matches($schedule, CarbonImmutable::parse('2026-03-09 04:00:00', 'UTC')));
    }
}
