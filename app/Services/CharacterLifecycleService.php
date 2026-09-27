<?php

namespace App\Services;

use App\Models\Character;
use App\Support\CavernasRules;
use Illuminate\Support\Carbon;

class CharacterLifecycleService
{
    public function advanceWeek(Character $character, ?Carbon $now = null): Character
    {
        $now ??= now();
        if ($character->is_frozen || $character->status === 'deceased') {
            return $character->refresh();
        }
        $character->age_moons = (float) $character->age_moons + 0.5;

        if (! $character->role_locked) {
            $character->role = $character->calculatedRole();
        }

        $lastActivity = $character->last_ic_post_at;
        if (! $lastActivity || $lastActivity->lte($now->copy()->subWeek())) {
            $character->energy = max(0, $character->energy - 20);
        }

        if (CavernasRules::shouldDieOfOldAge($character)) {
            $character->status = 'deceased';
            $character->energy = 0;
            $character->died_at = $now;
            $character->archived_at = $now;
        } elseif ($character->status === 'inactive') {
            $inactiveAt = $character->inactive_at ?: $character->updated_at;
            if ($inactiveAt && Carbon::parse($inactiveAt)->addWeeks(2)->lte($now)) {
                $character->status = 'deceased';
                $character->died_at = $now;
                $character->archived_at = $now;
            }
        } elseif ($character->energy <= 0 && $character->status === 'active') {
            $character->status = 'inactive';
            $character->inactive_at = $now;
        }

        if ($character->status === 'deceased' && ! $character->died_at) {
            $character->status = 'deceased';
            $character->died_at = $now;
            $character->archived_at = $character->archived_at ?: $now;
        }

        $character->save();

        return $character->refresh();
    }

    public function litterSize(bool $mates, bool $sameAllegiance): int
    {
        $maximum = $mates ? 12 : 6;
        $weights = $mates && $sameAllegiance
            ? [1 => 1, 2 => 2, 3 => 3, 4 => 5, 5 => 6, 6 => 7, 7 => 7, 8 => 6, 9 => 5, 10 => 3, 11 => 2, 12 => 1]
            : [1 => 5, 2 => 8, 3 => 10, 4 => 10, 5 => 8, 6 => 5];
        $roll = random_int(1, array_sum($weights));
        foreach ($weights as $size => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return min($size, $maximum);
            }
        }

        return $maximum;
    }

    public function kitOutcome(bool $differentAllegiances): array
    {
        if (! $differentAllegiances) {
            return ['status' => 'surviving', 'has_disability' => false];
        }

        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 70 => ['status' => 'deceased', 'has_disability' => false],
            $roll <= 85 => ['status' => 'surviving', 'has_disability' => true],
            default => ['status' => 'surviving', 'has_disability' => false],
        };
    }
}
