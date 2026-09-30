<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\ForumCategory;
use App\Models\Litter;
use App\Models\ModerationReport;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Models\SidebarModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_pages_use_one_save_and_bulk_update_all_rows(): void
    {
        $admin = $this->admin();
        $category = ForumCategory::create(['name' => 'Old category', 'sort_order' => 0]);
        $board = $category->boards()->create(['name' => 'Old board', 'slug' => 'old-board', 'is_ic' => false, 'sort_order' => 0]);
        $module = SidebarModule::create(['title' => 'Old module', 'body' => 'Old copy', 'placement' => 'left', 'sort_order' => 0, 'is_enabled' => true]);

        foreach (['admin.content.categories', 'admin.content.boards', 'admin.content.modules'] as $route) {
            $response = $this->actingAs($admin)->get(route($route))->assertOk();
            $this->assertSame(1, substr_count($response->getContent(), '>Save changes<'));
        }

        $this->actingAs($admin)->patch(route('admin.content.categories.bulk-update'), ['categories' => [$category->id => ['name' => 'New category', 'description' => 'Updated', 'sort_order' => 2]]])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.content.boards.bulk-update'), ['boards' => [$board->id => ['forum_category_id' => $category->id, 'name' => 'New board', 'description' => 'Updated', 'sort_order' => 3, 'is_ic' => 1]]])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.content.modules.bulk-update'), ['modules' => [$module->id => ['title' => 'New module', 'body' => 'New copy', 'link_text' => null, 'link_url' => null, 'placement' => 'right', 'sort_order' => 4, 'is_enabled' => 0]]])->assertRedirect();

        $this->assertDatabaseHas('forum_categories', ['id' => $category->id, 'name' => 'New category']);
        $this->assertDatabaseHas('forum_boards', ['id' => $board->id, 'name' => 'New board', 'is_ic' => 1]);
        $this->assertDatabaseHas('sidebar_modules', ['id' => $module->id, 'title' => 'New module', 'is_enabled' => 0]);
    }

    public function test_users_and_rules_use_one_save_and_bulk_update(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['role' => 'registered', 'status' => 'pending']);
        $registered = Role::where('slug', 'registered')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $category = RuleCategory::create(['name' => 'Old rules', 'sort_order' => 0]);
        $rule = Rule::create(['rule_category_id' => $category->id, 'title' => 'Old title', 'description' => 'Old description', 'sort_order' => 0]);

        $usersPage = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $rulesPage = $this->actingAs($admin)->get(route('admin.rules.index'))->assertOk();
        $this->assertSame(1, substr_count($usersPage->getContent(), '>Save changes<'));
        $this->assertSame(1, substr_count($rulesPage->getContent(), '>Save changes<'));

        $this->actingAs($admin)->patch(route('admin.users.bulk-update'), ['users' => [
            $admin->id => ['status' => 'approved', 'roles' => [$adminRole->id]],
            $member->id => ['status' => 'approved', 'roles' => [$registered->id]],
        ]])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.rules.bulk-update'), [
            'categories' => [$category->id => ['name' => 'New rules', 'sort_order' => 1]],
            'rules' => [$rule->id => ['rule_category_id' => $category->id, 'title' => 'New title', 'description' => 'New description', 'sort_order' => 2]],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'status' => 'approved']);
        $this->assertTrue($member->fresh()->hasRole('registered'));
        $this->assertDatabaseHas('rule_categories', ['id' => $category->id, 'name' => 'New rules']);
        $this->assertDatabaseHas('rules', ['id' => $rule->id, 'title' => 'New title']);
    }

    public function test_litters_and_reports_use_one_save_and_bulk_update(): void
    {
        $staff = User::factory()->create(['role' => 'moderator', 'status' => 'approved']);
        $female = $this->character(User::factory()->create(), 'female', 'Fawn');
        $male = $this->character(User::factory()->create(), 'male', 'Briar');
        $pregnancy = Pregnancy::create(['female_character_id' => $female->id, 'male_character_id' => $male->id, 'conceived_at' => now()->subWeeks(4), 'due_at' => now(), 'status' => 'birthed']);
        $litter = Litter::create(['pregnancy_id' => $pregnancy->id, 'kit_count' => 1, 'surviving_count' => 1, 'born_at' => now()]);
        $kit = $litter->kits()->create(['status' => 'surviving', 'sex' => 'female', 'energy' => 70, 'has_disability' => false]);
        $forumCategory = ForumCategory::create(['name' => 'Reports', 'sort_order' => 1]);
        $board = $forumCategory->boards()->create(['name' => 'Board', 'slug' => 'report-board', 'is_ic' => false]);
        $thread = $board->threads()->create(['user_id' => $female->user_id, 'title' => 'Thread', 'slug' => 'report-thread']);
        $post = $thread->posts()->create(['user_id' => $female->user_id, 'body' => 'Reported body.', 'is_ic' => false]);
        $report = ModerationReport::create(['user_id' => $male->user_id, 'reportable_type' => $post::class, 'reportable_id' => $post->id, 'reason' => 'A valid report reason.', 'status' => 'open']);

        $littersPage = $this->actingAs($staff)->get(route('staff.litters'))->assertOk();
        $reportsPage = $this->actingAs($staff)->get(route('staff.reports'))->assertOk();
        $this->assertSame(1, substr_count($littersPage->getContent(), '>Save changes<'));
        $this->assertSame(1, substr_count($reportsPage->getContent(), '>Save changes<'));

        $this->actingAs($staff)->patch(route('staff.litters.bulk-update'), ['kits' => [$kit->id => ['status' => 'deceased', 'has_disability' => 1]]])->assertRedirect();
        $this->actingAs($staff)->patch(route('staff.reports.bulk-resolve'), ['reports' => [$report->id => ['status' => 'resolved', 'resolution' => 'Reviewed and resolved.']]])->assertRedirect();

        $this->assertDatabaseHas('litter_kits', ['id' => $kit->id, 'status' => 'deceased', 'has_disability' => 1]);
        $this->assertDatabaseHas('moderation_reports', ['id' => $report->id, 'status' => 'resolved', 'reviewed_by' => $staff->id]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $admin->roles()->sync([Role::where('slug', 'admin')->firstOrFail()->id]);

        return $admin;
    }

    private function character(User $user, string $sex, string $name): Character
    {
        return Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => $sex, 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
    }
}
