<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Services\BreedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use DomainException;
use Tests\TestCase;

class BreedingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_cannot_breed_their_own_characters(): void
    {
        $user = User::factory()->create();
        $female = $this->character($user, 'female', 'Fawn');
        $male = $this->character($user, 'male', 'Briar');

        $this->expectException(DomainException::class);
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

        $this->expectException(DomainException::class);
        app(BreedingService::class)->beginPregnancy($female, $male, false);
    }

    public function test_female_with_an_apprentice_cannot_become_pregnant(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $apprentice = $this->character(User::factory()->create(), 'female', 'Ash');
        $apprentice->update(['age_moons' => 8, 'role' => 'apprentice']);
        $apprentice->relationships()->create(['related_character_id' => $female->id, 'type' => 'mentor', 'status' => 'accepted']);

        $this->expectException(DomainException::class);
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

    public function test_locked_roles_become_queens_and_return_to_the_previous_role_at_six_moons(): void
    {
        $conceived = Carbon::parse('2026-01-01 10:00:00');
        $birth = $conceived->copy()->addWeeks(4);
        $sixMoons = $birth->copy()->addWeeks(12);
        foreach (['leader' => 1000, 'deputy' => 500, 'medicine_cat' => 35000] as $role => $cost) {
            $owner = User::factory()->create();
            $female = $this->character($owner, 'female', 'Fawn');
            $female->update(['role' => $role, 'role_locked' => true, 'age_moons' => 24]);
            $male = $this->character(User::factory()->create(), 'male', 'Briar');
            DB::table('cricket_ledger')->insert(['user_id' => $owner->id, 'amount' => $cost, 'type' => 'grant', 'description' => 'Funds']);
            $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, false, $conceived);
            $this->assertSame('queen', $female->fresh()->role);
            $this->assertTrue($female->fresh()->role_locked);
            $this->assertSame($role, $pregnancy->previous_female_role);
            $this->assertDatabaseHas('cricket_ledger', ['user_id' => $owner->id, 'type' => 'breeding', 'amount' => -$cost]);
            app(BreedingService::class)->giveBirth($pregnancy, $birth);
            $female->refresh()->update(['energy' => 100, 'last_ic_post_at' => $sixMoons]);
            $service = app(\App\Services\CharacterLifecycleService::class);
            $this->assertSame('queen', $service->advanceWeek($female->fresh(), $sixMoons->copy()->subSecond())->role);
            $this->assertSame($role, $service->advanceWeek($female->fresh(), $sixMoons)->role);
            $this->assertTrue($female->fresh()->role_locked);
        }
    }

    public function test_a_nursing_queen_cannot_breed_until_six_moons_then_can_breed_again(): void
    {
        $conceived = Carbon::parse('2026-01-01');
        $birth = $conceived->copy()->addWeeks(4);
        $sixMoons = $birth->copy()->addWeeks(12);
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $breeding = app(BreedingService::class);
        $pregnancy = $breeding->beginPregnancy($female, $male, false, $conceived);
        $breeding->giveBirth($pregnancy, $birth);
        $female->refresh()->update(['energy' => 100, 'last_ic_post_at' => $sixMoons]);
        try {
            $breeding->beginPregnancy($female->fresh(), $male, false, $sixMoons->copy()->subSecond());
            $this->fail('A nursing queen was allowed to breed.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('six moons', $exception->getMessage());
        }
        $restored = app(\App\Services\CharacterLifecycleService::class)->advanceWeek($female->fresh(), $sixMoons);
        $this->assertSame('warrior', $restored->role);
        $newPregnancy = $breeding->beginPregnancy($restored, $male, false, $sixMoons);
        $this->assertSame('warrior', $newPregnancy->previous_female_role);
        $this->assertSame('queen', $restored->fresh()->role);
    }

    public function test_graduated_and_deceased_apprentices_do_not_block_pregnancy(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $graduate = $this->character(User::factory()->create(), 'female', 'Ash');
        $graduate->update(['role' => 'warrior']);
        $deceased = $this->character(User::factory()->create(), 'male', 'Birch');
        $deceased->update(['role' => 'apprentice', 'status' => 'deceased']);
        foreach ([$graduate, $deceased] as $apprentice) {
            $apprentice->relationships()->create(['related_character_id' => $female->id, 'type' => 'mentor', 'status' => 'accepted']);
        }
        $this->assertFalse($female->hasCurrentApprentice());
        $this->assertSame('pregnant', app(BreedingService::class)->beginPregnancy($female, $male, false)->status);
    }

    public function test_breeding_after_nursing_before_the_weekly_tick_preserves_the_original_role_cost(): void
    {
        $conceived = Carbon::parse('2026-01-01');
        $owner = User::factory()->create();
        $female = $this->character($owner, 'female', 'Fawn');
        $female->update(['role' => 'leader', 'role_locked' => true]);
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        DB::table('cricket_ledger')->insert(['user_id' => $owner->id, 'amount' => 2000, 'type' => 'grant', 'description' => 'Funds']);
        $breeding = app(BreedingService::class);
        $pregnancy = $breeding->beginPregnancy($female, $male, false, $conceived);
        $birth = $pregnancy->due_at->copy();
        $breeding->giveBirth($pregnancy, $birth);
        $female->refresh()->update(['energy' => 100]);
        $this->assertSame('queen', $female->role);
        $newPregnancy = $breeding->beginPregnancy($female, $male, false, $birth->copy()->addWeeks(12));
        $this->assertSame('leader', $newPregnancy->previous_female_role);
        $this->assertSame(2, DB::table('cricket_ledger')->where('user_id', $owner->id)->where('amount', -1000)->count());
    }

    public function test_energy_thresholds_are_fifty_for_females_and_twenty_five_for_males(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $breeding = app(BreedingService::class);
        foreach ([[49, 25], [50, 24]] as [$femaleEnergy, $maleEnergy]) {
            $female->update(['energy' => $femaleEnergy]);
            $male->update(['energy' => $maleEnergy]);
            try {
                $breeding->validateRequest($female, $male, $female->user_id);
                $this->fail('An insufficient-energy pair was allowed to breed.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString('energy', $exception->getMessage());
            }
        }
        $female->update(['energy' => 50]);
        $male->update(['energy' => 25]);
        $this->assertSame('pregnant', $breeding->beginPregnancy($female, $male, false)->status);
    }

    private function character(User $user, string $sex, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => $sex, 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
