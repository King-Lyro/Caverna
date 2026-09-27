<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use App\Services\BreedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_due_pregnancy_creates_surviving_kits_and_marks_it_birthed(): void
    {
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $pregnancy = app(BreedingService::class)->beginPregnancy($female, $male, true, Carbon::parse('2026-09-01'));
        $pregnancy->update(['due_at' => Carbon::parse('2026-09-28')]);

        $litter = app(BreedingService::class)->giveBirth($pregnancy->fresh(), Carbon::parse('2026-09-29'));

        $this->assertSame($litter->kit_count, $litter->surviving_count);
        $this->assertSame('birthed', $pregnancy->fresh()->status);
        $this->assertCount($litter->kit_count, $litter->kits);
        $this->assertSame(30, $female->fresh()->energy);
        $this->assertSame('warrior', $female->fresh()->role);
        $this->assertNotNull($litter->kits->first()->character_id);
        $this->assertDatabaseHas('character_relationships', ['character_id' => $litter->kits->first()->character_id, 'related_character_id' => $female->id, 'type' => 'parent', 'status' => 'accepted']);
    }

    private function character(User $user, string $sex, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => $sex, 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
