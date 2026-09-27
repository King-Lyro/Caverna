<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Services\CharacterLifecycleService;
use Illuminate\Console\Command;

class AdvanceCharacterLifecycle extends Command
{
    protected $signature = 'cavernas:advance-lifecycle';

    protected $description = 'Advance character ages, energy, inactivity, and death states by one week.';

    public function handle(CharacterLifecycleService $lifecycle): int
    {
        Character::query()->whereIn('status', ['active', 'inactive'])->chunkById(100, function ($characters) use ($lifecycle) {
            foreach ($characters as $character) {
                $lifecycle->advanceWeek($character);
            }
        });

        $this->info('Character lifecycle advanced.');

        return self::SUCCESS;
    }
}
