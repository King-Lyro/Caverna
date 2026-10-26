<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_member_can_view_account_settings(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee($user->email);
    }

    public function test_member_can_update_profile_and_privacy_settings(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'New Name',
            'bio' => 'A short profile.',
            'pronouns' => 'they/them',
            'age' => 30,
            'timezone' => 'America/New_York',
            'hide_personal_info' => '1',
            'is_absent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'hide_personal_info' => true, 'is_absent' => true]);
    }

    public function test_account_settings_do_not_offer_an_avatar_reset_and_regular_save_keeps_avatar(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'avatar_path' => 'avatars/custom.png']);
        $this->actingAs($user)->get(route('account.edit'))->assertOk()
            ->assertDontSee('remove_avatar')->assertDontSee('Use default avatar')
            ->assertDontSee('Without an uploaded avatar');
        $this->patch(route('account.update'), ['name' => $user->name])->assertSessionHasNoErrors();
        $this->assertSame('avatars/custom.png', $user->fresh()->avatar_path);
    }

    public function test_member_can_still_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'approved', 'avatar_path' => 'avatars/custom.png']);
        $this->actingAs($user)->patch(route('account.update'), [
            'name' => $user->name, 'avatar' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasNoErrors();
        $this->assertNotSame('avatars/custom.png', $user->fresh()->avatar_path);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }
}
