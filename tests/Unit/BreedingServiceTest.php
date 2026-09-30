<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Services\BreedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BreedingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_cannot_breed_their_own_characters(): void
    {
        $user = User::factory()->create();
        $female = $this->character($user, 'female', 'Fawn');
        $male = $this->character($user, 'male', 'Briar');

        $this->expectException(RuntimeException::class);
        app(BreedingService::class)->beginPregnancy($female, $male, false);
    }

    public function test_pregnancy_is_due_after_four_weeks(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $female->mates()->attach($male->id, ['accepted_at' => now()]);
        $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, true, Carbon::parse('2026-09-01'));

        $this->assertTrue($pregnancy->due_at->equalTo(Carbon::parse('2026-09-29')));
        $this->assertTrue($pregnancy->mates);
        $this->assertSame('queen', $female->fresh()->role);
    }

    public function test_characters_under_twelve_moons_cannot_breed(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $female->update(['age_moons' => 11.5]);

        $this->expectException(RuntimeException::class);
        app(BreedingService::class)->beginPregnancy($female, $male, false);
    }

    public function test_female_with_an_apprentice_cannot_become_pregnant(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $apprentice = $this->character(User::factory()->create(), 'female', 'Ash');
        $apprentice->relationships()->create(['related_character_id' => $female->id, 'type' => 'mentor', 'status' => 'accepted']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('apprentice');
        app(BreedingService::class)->beginPregnancy($female, $male, false);
    }

    public function test_only_the_requesting_owner_pays_the_breeding_cost(): void
    {
        $requester = User::factory()->create();
        $partner = User::factory()->create();
        $female = $this->character($requester, 'female', 'Fawn');
        $male = $this->character($partner, 'male', 'Briar');
        $female->update(['role' => 'leader']);
        $male->update(['role' => 'deputy']);
        DB::table('cricket_ledger')->insert(['user_id' => $requester->id, 'amount' => 1000, 'type' => 'grant', 'description' => 'Funds', 'created_at' => now(), 'updated_at' => now()]);

        app(BreedingService::class)->beginPregnancy($female, $male, false, null, $requester->id);

        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $requester->id, 'amount' => -1000, 'type' => 'breeding']);
        $this->assertDatabaseMissing('cricket_ledger', ['user_id' => $partner->id, 'amount' => -250]);
    }

    public function test_due_pregnancy_creates_surviving_kits_and_marks_it_birthed(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $female->mates()->attach($male->id, ['accepted_at' => now()]);
        $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, true, Carbon::parse('2026-09-01'));
        $pregnancy->update(['due_at' => Carbon::parse('2026-09-28')]);

        $litter = app(BreedingService::class)->giveBirth($pregnancy->fresh(), Carbon::parse('2026-09-29'));

        $this->assertSame($litter->kit_count, $litter->surviving_count);
        $this->assertSame('birthed', $pregnancy->fresh()->status);
        $this->assertCount($litter->kit_count, $litter->kits);
        $this->assertSame(30, $female->fresh()->energy);
        $this->assertSame('queen', $female->fresh()->role);
        $this->assertNotNull($litter->kits->first()->character_id);
        $this->assertDatabaseHas('character_relationships', ['character_id' => $litter->kits->first()->character_id, 'related_character_id' => $female->id, 'type' => 'parent', 'status' => 'accepted']);
    }

    public function test_queen_role_reverts_once_her_kits_are_six_moons_old(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $female->update(['role' => 'warrior']);
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, false, Carbon::parse('2026-09-01'));
        $pregnancy->update(['due_at' => Carbon::parse('2026-09-28')]);
        app(BreedingService::class)->giveBirth($pregnancy->fresh(), Carbon::parse('2026-09-29'));

        $stillQueen = app(\App\Services\CharacterLifecycleService::class)->advanceWeek($female->fresh(), Carbon::parse('2026-11-01'));
        $this->assertSame('queen', $stillQueen->role);

        $reverted = app(\App\Services\CharacterLifecycleService::class)->advanceWeek($female->fresh(), Carbon::parse('2026-12-23'));
        $this->assertSame('warrior', $reverted->role);
    }

    public function test_claiming_mates_without_a_recorded_pair_does_not_increase_litter_size(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');

        $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, true);

        $this->assertFalse($pregnancy->mates);
    }

    private function character(User $user, string $sex, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => $sex, 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
