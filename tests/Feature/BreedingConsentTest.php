<?php

namespace Tests\Feature;

use App\Models\BreedingRequest;
use App\Models\Character;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BreedingConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pregnancy_and_charge_wait_for_recipient_acceptance(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $female->update(['role' => 'leader']);
        DB::table('cricket_ledger')->insert([
            'user_id' => $sender->id,
            'amount' => 1500,
            'type' => 'admin_adjustment',
            'description' => 'Starting balance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id,
            'male_character_id' => $male->id,
        ])->assertRedirect(route('dashboard'));

        $breedingRequest = BreedingRequest::firstOrFail();
        $this->assertDatabaseCount('pregnancies', 0);
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $sender->id, 'amount' => 1500]);
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Breeding requests</span><strong>1</strong>', false)
            ->assertSee('Sent · Awaiting');
        $this->actingAs($recipient)->get(route('dashboard'))->assertOk()
            ->assertSee('Breeding requests</span><strong>1</strong>', false)
            ->assertSee(route('breeding.accept', $breedingRequest))
            ->assertSee(route('breeding.reject', $breedingRequest));

        $this->actingAs($recipient)->patch(route('breeding.accept', $breedingRequest))->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('breeding_requests', ['id' => $breedingRequest->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $sender->id, 'amount' => -1000, 'type' => 'breeding']);
        $this->assertSame(1, Pregnancy::count());
    }

    public function test_acceptance_fails_atomically_and_notifies_sender_when_sender_lacks_funds(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $female->update(['role' => 'leader']);
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id,
            'male_character_id' => $male->id,
        ])->assertRedirect(route('dashboard'));
        $breedingRequest = BreedingRequest::firstOrFail();

        $this->actingAs($recipient)->from(route('dashboard'))->patch(route('breeding.accept', $breedingRequest))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['breeding' => 'The sender lacks the required funds: The character owner does not have enough crickets.']);

        $this->assertDatabaseHas('breeding_requests', ['id' => $breedingRequest->id, 'status' => 'pending']);
        $this->assertDatabaseCount('pregnancies', 0);
        $this->assertDatabaseMissing('cricket_ledger', ['user_id' => $sender->id, 'type' => 'breeding']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $sender->id,
            'type' => \App\Notifications\SiteNotification::class,
        ]);
    }

    public function test_declining_a_request_does_not_charge_or_conceive_and_notifies_sender(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id,
            'male_character_id' => $male->id,
        ])->assertRedirect(route('dashboard'));
        $breedingRequest = BreedingRequest::firstOrFail();

        $this->actingAs($recipient)->patch(route('breeding.reject', $breedingRequest))->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('breeding_requests', ['id' => $breedingRequest->id, 'status' => 'rejected']);
        $this->assertDatabaseCount('pregnancies', 0);
        $this->assertDatabaseMissing('cricket_ledger', ['user_id' => $sender->id, 'type' => 'breeding']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $sender->id,
            'type' => \App\Notifications\SiteNotification::class,
        ]);
    }

    public function test_acceptance_revalidates_both_characters_before_creating_pregnancy(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id,
            'male_character_id' => $male->id,
        ])->assertRedirect(route('dashboard'));
        $breedingRequest = BreedingRequest::firstOrFail();
        $male->update(['is_frozen' => true]);

        $this->actingAs($recipient)->from(route('dashboard'))->patch(route('breeding.accept', $breedingRequest))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('breeding');

        $this->assertDatabaseHas('breeding_requests', ['id' => $breedingRequest->id, 'status' => 'pending']);
        $this->assertDatabaseCount('pregnancies', 0);
    }

    public function test_acceptance_fails_if_the_female_acquires_an_apprentice_after_requesting(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id, 'male_character_id' => $male->id,
        ])->assertRedirect();
        $apprentice = $this->character($recipient, 'male', 'Ash');
        $apprentice->update(['role' => 'apprentice', 'age_moons' => 8]);
        $apprentice->relationships()->create(['related_character_id' => $female->id, 'type' => 'mentor', 'status' => 'accepted']);
        $request = BreedingRequest::firstOrFail();
        $this->actingAs($recipient)->patch(route('breeding.accept', $request))->assertSessionHasErrors('breeding');
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertDatabaseCount('pregnancies', 0);
    }

    public function test_sender_and_unrelated_member_cannot_accept_or_decline(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id, 'male_character_id' => $male->id,
        ])->assertRedirect();
        $request = BreedingRequest::firstOrFail();
        $this->patch(route('breeding.accept', $request))->assertSessionHas('error');
        $this->patch(route('breeding.reject', $request))->assertSessionHas('error');
        $outsider = User::factory()->create(['status' => 'approved']);
        $this->actingAs($outsider)->patch(route('breeding.accept', $request))->assertForbidden();
        $this->patch(route('breeding.reject', $request))->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertDatabaseCount('pregnancies', 0);
    }

    public function test_birth_is_processed_at_site_midnight_on_the_twenty_ninth_calendar_day(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-01-01 13:30:00'));
        [$sender, $recipient, $female, $male] = $this->pair();
        $this->actingAs($sender)->post(route('breeding.store'), [
            'female_character_id' => $female->id, 'male_character_id' => $male->id,
        ])->assertRedirect();
        $this->actingAs($recipient)->patch(route('breeding.accept', BreedingRequest::firstOrFail()))->assertRedirect();
        $pregnancy = Pregnancy::firstOrFail();
        $this->assertSame('2026-01-29 00:00:00', $pregnancy->due_at->format('Y-m-d H:i:s'));
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-01-28 23:59:59'));
        $this->artisan('cavernas:process-pregnancies')->assertSuccessful();
        $this->assertDatabaseCount('litters', 0);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-01-29 00:00:00'));
        $this->artisan('cavernas:process-pregnancies')->assertSuccessful();
        $this->assertDatabaseCount('litters', 1);
        $this->assertSame('birthed', $pregnancy->fresh()->status);
        $this->assertSame('queen', $female->fresh()->role);
        $this->artisan('cavernas:process-pregnancies')->assertSuccessful();
        $this->assertDatabaseCount('litters', 1);
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $birthEvent = collect($events)->first(fn ($event) => str_contains($event->command ?? '', 'cavernas:process-pregnancies'));
        $this->assertNotNull($birthEvent);
        $this->assertSame('0 0 * * *', $birthEvent->expression);
        $this->travelBack();
    }

    public function test_upgrade_corrects_existing_locked_pregnancies_without_losing_the_original_role(): void
    {
        [$sender, $recipient, $female, $male] = $this->pair();
        $female->update(['role' => 'leader', 'role_locked' => true]);
        $pregnancy = Pregnancy::create([
            'female_character_id' => $female->id, 'male_character_id' => $male->id,
            'conceived_at' => now(), 'due_at' => now()->addWeeks(4), 'status' => 'pregnant',
        ]);
        (require database_path('migrations/2026_10_15_000006_correct_pregnancy_roles_and_birth_times.php'))->up();
        $this->assertSame('queen', $female->fresh()->role);
        $this->assertTrue($female->fresh()->role_locked);
        $this->assertSame('leader', $pregnancy->fresh()->previous_female_role);
        $this->assertSame('00:00:00', $pregnancy->fresh()->due_at->format('H:i:s'));
        (require database_path('migrations/2026_10_15_000006_correct_pregnancy_roles_and_birth_times.php'))->up();
        $this->assertSame('leader', $pregnancy->fresh()->previous_female_role);
    }

    private function pair(): array
    {
        $sender = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $female = $this->character($sender, 'female', 'female');
        $male = $this->character($recipient, 'male', 'male');

        return [$sender, $recipient, $female, $male];
    }

    private function character(User $user, string $sex, string $name): Character
    {
        return Character::create([
            'user_id' => $user->id,
            'name' => $name,
            'sex' => $sex,
            'age_moons' => 12,
            'allegiance' => 'ThunderClan',
            'role' => 'warrior',
            'looks' => 'A careful gaze.',
            'appearance' => 'Appearance.',
            'personality' => 'Personality.',
            'history' => 'History.',
            'energy' => 100,
            'status' => 'active',
        ]);
    }
}
