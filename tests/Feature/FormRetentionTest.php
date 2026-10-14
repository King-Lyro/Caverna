<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_enhanced_file_form_returns_validation_errors_without_redirecting(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['status' => 'approved']);
        $avatar = UploadedFile::fake()->image('selected.png');
        $headers = ['Accept' => 'application/json', 'X-Preserve-Form' => '1'];

        $this->actingAs($member)->withHeaders($headers)->patch(route('account.update'), [
            'name' => '',
            'bio' => 'Keep this introduction.',
            'avatar' => $avatar,
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame([], Storage::disk('public')->allFiles('avatars'));

        $response = $this->withHeaders($headers)->from(route('account.edit'))->patch(route('account.update'), [
            'name' => 'Corrected name',
            'bio' => 'Keep this introduction.',
            'avatar' => $avatar,
        ]);
        $response->assertOk()->assertJsonPath('redirect', route('account.edit'));
        Storage::disk('public')->assertExists($member->fresh()->avatar_path);
        $this->assertSame('Keep this introduction.', $member->fresh()->bio);
    }

    public function test_redirect_based_validation_is_returned_as_an_inline_error(): void
    {
        $this->withHeaders(['Accept' => 'application/json', 'X-Preserve-Form' => '1'])
            ->post(route('login'), ['email' => 'unknown@example.test', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_pending_login_failure_returns_a_fresh_csrf_token_for_resubmission(): void
    {
        $member = User::factory()->create(['status' => 'pending', 'role' => 'registered']);
        $this->withHeaders(['Accept' => 'application/json', 'X-Preserve-Form' => '1'])
            ->post(route('login'), ['email' => $member->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email')
            ->assertJsonStructure(['csrf_token']);
    }

    public function test_old_input_payload_does_not_expose_passwords(): void
    {
        $this->withSession(['_old_input' => [
            'email' => 'member@example.test',
            'password' => 'never-render-this-password',
            'password_confirmation' => 'never-render-this-confirmation',
            'current_password' => 'never-render-this-current-password',
        ]])->get(route('login'))->assertOk()
            ->assertSee('member@example.test')
            ->assertDontSee('never-render-this-password')
            ->assertDontSee('never-render-this-confirmation')
            ->assertDontSee('never-render-this-current-password');
    }
}
