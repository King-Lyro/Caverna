<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('id')->get(),
            'search' => $search,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);
        $roleIds = array_values(array_unique(array_map('intval', $validated['roles'] ?? [])));
        $adminRoleId = Role::where('slug', 'admin')->value('id');
        $hasAdmin = in_array((int) $adminRoleId, $roleIds, true);
        $adminCount = User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->count();

        abort_if($user->is($request->user()) && ! $hasAdmin, 422, 'You cannot remove your own administrator role.');
        abort_if($user->is($request->user()) && $validated['status'] !== 'approved', 422, 'You cannot unapprove your own account.');
        abort_if($user->isNot($request->user()) && $user->isAdmin() && ! $hasAdmin && $adminCount <= 1, 422, 'The last administrator cannot be removed.');

        $before = ['status' => $user->status, 'roles' => $user->roles->pluck('slug')->values()->all()];
        $user->forceFill([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? ($user->approved_at ?: now()) : null,
        ])->save();
        $user->roles()->sync($roleIds);
        $user->load('roles');

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.admin_updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'changes' => ['before' => $before, 'after' => ['status' => $user->status, 'roles' => $user->roles->pluck('slug')->values()->all()]],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', $user->name.' was updated.');
    }
}
