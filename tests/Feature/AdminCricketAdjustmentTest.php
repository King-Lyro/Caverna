<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCricketAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adjustments_write_a_ledger_entry_and_audit_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $admin->roles()->sync([Role::where('slug', 'admin')->firstOrFail()->id]);
        $member = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('admin.users.crickets.adjust', $member), [
            'amount' => 250,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('cricket_ledger', [
            'user_id' => $member->id,
            'amount' => 250,
            'type' => 'admin_adjustment',
            'description' => 'Administrator balance adjustment.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.crickets_adjusted',
            'auditable_type' => User::class,
            'auditable_id' => $member->id,
        ]);
        $audit = AuditLog::where('action', 'user.crickets_adjusted')->firstOrFail();
        $this->assertSame(250, $audit->changes['amount']);
        $this->assertArrayNotHasKey('reason', $audit->changes);
        $this->assertSame(0, $audit->changes['balance_before']);
        $this->assertSame(250, $audit->changes['balance_after']);
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('name="reason"', false);
    }

    public function test_adjustment_requires_a_nonzero_amount(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $member = User::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->post(route('admin.users.crickets.adjust', $member), [
            'amount' => 0,
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('cricket_ledger', 0);
    }
}
