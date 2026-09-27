<?php

namespace Tests\Feature;

use App\Models\AdoptionApplication;
use App\Models\AdoptionListing;
use App\Models\Character;
use App\Models\CharacterSaleListing;
use App\Models\CharacterTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdoptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_member_can_claim_an_instant_adoption_listing(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $adopter = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Fern');
        $listing = AdoptionListing::create(['character_id' => $character->id, 'title' => 'Fern needs a home', 'description' => 'A gentle character looking for a new story.', 'claim_policy' => 'instant', 'status' => 'available']);

        $this->actingAs($adopter)->post(route('adoption.claim', $listing))->assertRedirect(route('characters.show', $character));

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'user_id' => $adopter->id, 'adopted' => 1]);
        $this->assertDatabaseHas('adoption_listings', ['id' => $listing->id, 'status' => 'claimed', 'claimed_by' => $adopter->id]);
    }

    public function test_application_listing_creates_a_pending_application_without_transfer(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $applicant = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Moss');
        $listing = AdoptionListing::create(['character_id' => $character->id, 'title' => 'Moss needs a home', 'description' => 'A thoughtful character looking for a new story.', 'claim_policy' => 'application', 'status' => 'available']);

        $this->actingAs($applicant)->post(route('adoption.apply', $listing), ['message' => 'I have a story and a safe place ready for Moss.'])->assertRedirect();

        $this->assertDatabaseHas('adoption_applications', ['adoption_listing_id' => $listing->id, 'user_id' => $applicant->id, 'status' => 'pending']);
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'user_id' => $owner->id, 'adopted' => 0]);
    }

    public function test_an_approved_owner_can_publish_a_rich_application_listing(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Stone');

        $this->actingAs($owner)->post(route('adoption.store'), [
            'character_id' => $character->id,
            'title' => 'Stone needs a home',
            'description' => 'A careful character looking for a long story.',
            'claim_policy' => 'application',
            'appearance' => 'Dark coat and bright eyes.',
            'adopter_notes' => 'The adopter may choose the future.',
            'contact_instructions' => 'Send an audition through Cavernas.',
        ])->assertRedirect(route('adoption.index'));

        $this->assertDatabaseHas('adoption_listings', ['character_id' => $character->id, 'owner_id' => $owner->id, 'claim_policy' => 'application', 'status' => 'available']);
    }

    public function test_listing_owner_can_approve_an_application_and_transfer_the_character(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $applicant = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Cedar');
        $listing = AdoptionListing::create(['character_id' => $character->id, 'owner_id' => $owner->id, 'title' => 'Cedar needs a home', 'description' => 'A character looking for a new story.', 'claim_policy' => 'application', 'status' => 'available']);
        $application = AdoptionApplication::create(['adoption_listing_id' => $listing->id, 'user_id' => $applicant->id, 'message' => 'I would love to write Cedar in a careful new story.']);

        $this->actingAs($owner)->patch(route('adoption.review', $application), ['status' => 'approved', 'reviewer_notes' => 'Approved.'])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'user_id' => $applicant->id, 'adopted' => 1]);
        $this->assertDatabaseHas('adoption_applications', ['id' => $application->id, 'status' => 'approved', 'reviewed_by' => $owner->id]);
        $this->assertDatabaseHas('adoption_listings', ['id' => $listing->id, 'status' => 'claimed', 'claimed_by' => $applicant->id]);
    }

    public function test_publishing_freezes_a_character_and_owner_can_withdraw_it(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Frost');

        $this->actingAs($owner)->post(route('adoption.store'), ['character_id' => $character->id, 'title' => 'Frost needs a home', 'description' => 'A character with a careful story.', 'claim_policy' => 'application'])->assertRedirect();
        $listing = AdoptionListing::firstOrFail();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'is_frozen' => 1, 'frozen_reason' => 'adoption']);

        $this->actingAs($owner)->patch(route('adoption.withdraw', $listing))->assertRedirect();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'is_frozen' => 0]);
        $this->assertDatabaseHas('adoption_listings', ['id' => $listing->id, 'status' => 'withdrawn']);
    }

    public function test_member_can_list_and_purchase_a_character_for_sale(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $buyer = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Cinder');
        DB::table('cricket_ledger')->insert(['user_id' => $buyer->id, 'amount' => 500, 'type' => 'grant', 'description' => 'Test funds', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($owner)->post(route('sales.store'), ['character_id' => $character->id, 'price' => 250])->assertRedirect();
        $listing = CharacterSaleListing::firstOrFail();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'is_frozen' => 1, 'frozen_reason' => 'sale']);

        $this->actingAs($buyer)->post(route('sales.purchase', $listing))->assertRedirect();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'user_id' => $buyer->id, 'is_frozen' => 0]);
        $this->assertDatabaseHas('character_sale_listings', ['id' => $listing->id, 'status' => 'sold', 'buyer_id' => $buyer->id]);
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $buyer->id, 'amount' => -250, 'type' => 'character_purchase']);
    }

    public function test_owner_can_gift_a_character_to_another_member(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $recipient = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Willow');

        $this->actingAs($owner)->post(route('characters.transfer', $character), ['recipient_id' => $recipient->id])->assertRedirect();
        $transfer = CharacterTransfer::firstOrFail();
        $this->actingAs($recipient)->patch(route('characters.transfer.accept', $transfer))->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseHas('character_transfers', ['id' => $transfer->id, 'status' => 'accepted']);
    }

    private function character(User $user, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
