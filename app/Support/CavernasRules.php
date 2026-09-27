<?php

namespace App\Support;

use App\Models\Character;

final class CavernasRules
{
    public static function roleForAge(float $ageMoons, string $allegiance): string
    {
        if ($allegiance === 'outsider' || in_array($allegiance, ['Kittypet', 'Loner', 'Rogue'], true)) {
            return match ($allegiance) {
                'Kittypet', 'Loner', 'Rogue' => strtolower($allegiance),
                default => 'outsider',
            };
        }

        return match (true) {
            $ageMoons < 6 => 'kit',
            $ageMoons < 12 => 'apprentice',
            $ageMoons < 130 => 'warrior',
            default => 'elder',
        };
    }

    public static function shouldDieOfOldAge(Character $character): bool
    {
        return (float) $character->age_moons >= 161 && $character->status !== 'deceased';
    }

    public static function clanPopulationCounts(): array
    {
        return Character::query()
            ->whereIn('allegiance', ['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'])
            ->whereIn('role', ['warrior', 'apprentice'])
            ->whereIn('status', ['active', 'inactive'])
            ->selectRaw('allegiance, count(*) as population')
            ->groupBy('allegiance')
            ->pluck('population', 'allegiance')
            ->all();
    }

    public static function clanCreationAllowed(string $allegiance): bool
    {
        if (! in_array($allegiance, ['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'], true)) {
            return true;
        }

        $counts = array_replace(array_fill_keys(['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'], 0), self::clanPopulationCounts());
        $lowest = min($counts);

        return $counts[$allegiance] <= ($lowest * 1.5);
    }
}
