<?php

namespace App\Services;

use App\Models\SiteThemeSchedule;
use Carbon\CarbonImmutable;

class ThemeCalendar
{
    public function occurrence(SiteThemeSchedule $schedule, int $year): array
    {
        if ($schedule->recurrence === 'once') {
            return [$schedule->starts_on, $schedule->ends_on];
        }
        $endYear = $year + ($schedule->ends_md < $schedule->starts_md ? 1 : 0);

        return [$this->annualDate($schedule->starts_md, $year), $this->annualDate($schedule->ends_md, $endYear)];
    }

    private function annualDate(string $monthDay, int $year): string
    {
        [$month, $day] = array_map('intval', explode('-', $monthDay));
        $first = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));

        return $first->day(min($day, $first->daysInMonth))->format('Y-m-d');
    }

    public function matches(SiteThemeSchedule $schedule, CarbonImmutable $now): bool
    {
        $date = $now->setTimezone(config('app.timezone'))->format('Y-m-d');
        foreach ([$now->setTimezone(config('app.timezone'))->year - 1, $now->setTimezone(config('app.timezone'))->year] as $year) {
            [$start, $end] = $this->occurrence($schedule, $year);
            if ($start <= $date && $date <= $end) {
                return true;
            }
        }

        return false;
    }

    public function overlaps(SiteThemeSchedule $a, SiteThemeSchedule $b): bool
    {
        if ($a->recurrence === 'once' && $b->recurrence === 'once') {
            return $a->starts_on <= $b->ends_on && $b->starts_on <= $a->ends_on;
        }
        if ($a->recurrence === 'once' || $b->recurrence === 'once') {
            $once = $a->recurrence === 'once' ? $a : $b;
            $annual = $a->recurrence === 'annual' ? $a : $b;
            for ($year = (int) substr($once->starts_on, 0, 4) - 1; $year <= (int) substr($once->ends_on, 0, 4); $year++) {
                [$start, $end] = $this->occurrence($annual, $year);
                if ($start <= $once->ends_on && $once->starts_on <= $end) {
                    return true;
                }
            }

            return false;
        }
        // Gregorian leap-day patterns repeat every 400 years; include boundary occurrences.
        $left = $right = [];
        for ($year = 1999; $year <= 2400; $year++) {
            $left[] = $this->occurrence($a, $year);
            $right[] = $this->occurrence($b, $year);
        }
        $i = $j = 0;
        while (isset($left[$i], $right[$j])) {
            if ($left[$i][0] <= $right[$j][1] && $right[$j][0] <= $left[$i][1]) {
                return true;
            }
            if ($left[$i][1] < $right[$j][1]) {
                $i++;
            } else {
                $j++;
            }
        }

        return false;
    }
}
