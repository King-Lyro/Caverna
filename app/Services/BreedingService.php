<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Litter;
use App\Models\Pregnancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BreedingService
{
    public function beginPregnancy(Character $female, Character $male, bool $mates, ?Carbon $now = null): Pregnancy
    {
        $now ??= now();
        if ($female->sex !== 'female' || $male->sex !== 'male') {
            throw new RuntimeException('Breeding requires one female and one male character.');
        }
        if ($female->user_id === $male->user_id) {
            throw new RuntimeException('Members cannot breed their own characters.');
        }
        if ($female->energy < 50 || $male->energy < 25) {
            throw new RuntimeException('Both characters need enough energy to breed.');
        }
        if (Pregnancy::where('female_character_id', $female->id)->where('status', 'pregnant')->exists()) {
            throw new RuntimeException('This character is already pregnant.');
        }

        return DB::transaction(function () use ($female, $male, $mates, $now) {
            $this->charge($female, $this->breedingCost($female));
            $this->charge($male, $this->breedingCost($male));

            return Pregnancy::create([
                'female_character_id' => $female->id,
                'male_character_id' => $male->id,
                'mates' => $mates,
                'conceived_at' => $now,
                'due_at' => $now->copy()->addWeeks(4),
                'status' => $mates ? 'pregnant' : 'pregnant',
            ]);
        });
    }

    public function giveBirth(Pregnancy $pregnancy, ?Carbon $now = null): Litter
    {
        $now ??= now();
        if ($pregnancy->status !== 'pregnant') {
            throw new RuntimeException('This pregnancy has already been processed.');
        }
        if ($pregnancy->due_at->gt($now)) {
            throw new RuntimeException('This pregnancy is not due yet.');
        }

        return DB::transaction(function () use ($pregnancy, $now) {
            $female = $pregnancy->female;
            $male = $pregnancy->male;
            $different = $female->allegiance !== $male->allegiance;
            $count = app(CharacterLifecycleService::class)->litterSize($pregnancy->mates, ! $different);
            $lowEnergy = $female->energy < 70;
            $female->forceFill(['energy' => max(0, $female->energy - 70)])->save();
            $litter = Litter::create(['pregnancy_id' => $pregnancy->id, 'kit_count' => $count, 'born_at' => $now]);
            $surviving = 0;
            $outcomes = [];
            for ($index = 0; $index < $count; $index++) {
                $outcome = $this->outcome($different, $lowEnergy);
                $outcomes[] = $outcome;
                if ($outcome['status'] === 'surviving') {
                    $surviving++;
                }
            }
            $femaleKitLimit = (int) ceil($surviving / 2);
            $femaleKitCount = 0;
            foreach ($outcomes as $outcome) {
                $ownerId = null;
                if ($outcome['status'] === 'surviving') {
                    $ownerId = $femaleKitCount < $femaleKitLimit ? $female->user_id : $male->user_id;
                    $femaleKitCount++;
                }
                $litter->kits()->create(['status' => $outcome['status'], 'sex' => random_int(0, 1) ? 'female' : 'male', 'coat_notes' => $this->inheritCoatNotes($female, $male), 'has_disability' => $outcome['has_disability'], 'owner_id' => $ownerId]);
            }
            $litter->update(['surviving_count' => $surviving]);
            $pregnancy->update(['status' => 'birthed']);

            return $litter->load('kits');
        });
    }

    private function outcome(bool $different, bool $lowEnergy): array
    {
        if (! $different && ! $lowEnergy) {
            return ['status' => 'surviving', 'has_disability' => false];
        }
        $deathChance = $different ? 70 : ($lowEnergy ? 45 : 0);
        $disabilityChance = $different ? 15 : ($lowEnergy ? 25 : 0);
        $roll = random_int(1, 100);

        return $roll <= $deathChance
            ? ['status' => 'deceased', 'has_disability' => false]
            : ['status' => 'surviving', 'has_disability' => $roll <= $deathChance + $disabilityChance];
    }

    private function breedingCost(Character $character): int
    {
        return match ($character->role) {
            'medicine_cat' => 35000,
            'leader' => $character->sex === 'female' ? 1000 : 500,
            'deputy' => $character->sex === 'female' ? 500 : 250,
            default => 0,
        };
    }

    private function inheritCoatNotes(Character $female, Character $male): string
    {
        return 'Inherited traits: '.$female->looks.' / '.$male->looks.'. Staff may refine this result.';
    }

    private function charge(Character $character, int $cost): void
    {
        if ($cost === 0) {
            return;
        }
        $balance = (int) DB::table('cricket_ledger')->where('user_id', $character->user_id)->sum('amount');
        if ($balance < $cost) {
            throw new RuntimeException('The character owner does not have enough crickets.');
        }
        DB::table('cricket_ledger')->insert(['user_id' => $character->user_id, 'amount' => -$cost, 'type' => 'breeding', 'description' => 'Breeding cost for '.$character->name, 'created_at' => now(), 'updated_at' => now()]);
    }
}
