<?php

namespace Tests\Feature;

use App\Models\AdoptionApplication;
use App\Models\AdoptionListing;
use App\Models\Character;
use App\Models\CharacterSaleListing;
use App\Models\CharacterTransfer;
use App\Models\ContentPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdoptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_adoption_index_separates_editorial_content_from_listings(): void
    {
        $this->get(route('adoption.index'))->assertOk()
            ->assertSee('class="adoption-intro guide-article"', false)
            ->assertSee('class="adoption-listings"', false)
            ->assertSee('Adoption<br />', false)
            ->assertSee('Available characters');
    }

    public function test_adoption_index_preserves_listing_images_types_and_descriptions(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Fern');
        $listing = AdoptionListing::create([
            'character_id' => $character->id, 'title' => 'Fern needs a home',
            'description' => 'A gentle character looking for a new story.',
            'images' => ['https://example.com/fern.jpg'], 'claim_policy' => 'application', 'status' => 'available',
        ]);

        $this->get(route('adoption.index'))->assertOk()
            ->assertSee('Fern needs a home')->assertSee('A gentle character looking for a new story.')
            ->assertSee('Application')->assertSee('https://example.com/fern.jpg')
            ->assertSee(route('adoption.show', $listing));
    }

    public function test_only_admins_can_edit_adoption_page_content(): void
    {
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.adoption.content.edit'))->assertForbidden();
        $this->actingAs($member)->patch(route('admin.adoption.content.update'), ['title' => 'Changed'])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.adoption.content.edit'))->assertOk()->assertSee('data-article-editor', false);
        $this->actingAs($admin)->patch(route('admin.adoption.content.update'), [
            'eyebrow' => 'New beginnings', 'title' => "Find your\nnext story.",
            'intro' => 'Meet characters ready for a new chapter.',
            'body' => "## Before you apply\n\n| Path | Next step |\n| --- | --- |\n| Instant | Claim now |\n\n![Fern](/storage/adoption/fern.png)\n\n<script>alert('unsafe')</script>",
        ])->assertRedirect();

        $this->assertDatabaseHas('content_pages', ['slug' => 'adoption', 'eyebrow' => 'New beginnings']);
        $this->get(route('adoption.index'))->assertOk()->assertSee('New beginnings')
            ->assertSee('<h2>Before you apply</h2>', false)->assertSee('<table>', false)
            ->assertSee('src="/storage/adoption/fern.png"', false)->assertDontSee('<script>', false);
    }

    public function test_only_admins_can_upload_safe_adoption_page_images(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->post(route('admin.adoption.content.images.store'), ['image' => UploadedFile::fake()->image('fern.png')])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $response = $this->actingAs($admin)->post(route('admin.adoption.content.images.store'), ['image' => UploadedFile::fake()->image('fern.png')]);
        $response->assertOk()->assertJsonStructure(['url']);
        Storage::disk('public')->assertExists('adoption/'.basename($response->json('url')));
        $this->actingAs($admin)->post(route('admin.adoption.content.images.store'), ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
    }

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

    public function test_sales_index_keeps_listing_actions_in_an_adoption_style_panel(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $character = $this->character($owner, 'Fern');
        $listing = CharacterSaleListing::create(['character_id' => $character->id, 'owner_id' => $owner->id, 'price' => 250, 'status' => 'available']);

        $this->get(route('sales.index'))->assertOk()
            ->assertSee('class="sales-index-page"', false)
            ->assertSee('class="sales-intro guide-article"', false)
            ->assertSee('class="sales-listings adoption-listings"', false)
            ->assertSee('Fern')->assertSee('250')->assertSee('Log in to purchase');
        $this->actingAs($owner)->get(route('sales.index'))->assertOk()->assertSee(route('sales.withdraw', $listing));
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
