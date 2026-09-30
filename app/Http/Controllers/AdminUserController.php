<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'users' => ['required', 'array'],
            'users.*.status' => ['required', 'in:pending,approved,rejected'],
            'users.*.roles' => ['nullable', 'array'],
            'users.*.roles.*' => ['integer', 'exists:roles,id'],
        ]);
        $adminRoleId = (int) Role::where('slug', 'admin')->value('id');
        $submitted = collect($validated['users']);
        $remainingAdmins = User::with('roles')->get()->filter(function (User $user) use ($submitted, $adminRoleId): bool {
            if (! $user->isAdmin()) {
                return false;
            }
            if (! $submitted->has($user->id)) {
                return true;
            }

            return in_array($adminRoleId, array_map('intval', $submitted->get($user->id)['roles'] ?? []), true);
        })->count();
        abort_if($remainingAdmins < 1, 422, 'The last administrator cannot be removed.');
        $self = $submitted->get($request->user()->id);
        if ($self) {
            abort_if($self['status'] !== 'approved', 422, 'You cannot unapprove your own account.');
            abort_unless(in_array($adminRoleId, array_map('intval', $self['roles'] ?? []), true), 422, 'You cannot remove your own administrator role.');
        }

        DB::transaction(function () use ($validated, $request): void {
            foreach ($validated['users'] as $id => $fields) {
                $user = User::with('roles')->findOrFail($id);
                $roleIds = array_values(array_unique(array_map('intval', $fields['roles'] ?? [])));
                $before = ['status' => $user->status, 'roles' => $user->roles->pluck('slug')->values()->all()];
                $user->forceFill(['status' => $fields['status'], 'approved_at' => $fields['status'] === 'approved' ? ($user->approved_at ?: now()) : null])->save();
                $user->roles()->sync($roleIds);
                $user->load('roles');
                AuditLog::create(['user_id' => $request->user()->id, 'action' => 'user.admin_updated', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'changes' => ['before' => $before, 'after' => ['status' => $user->status, 'roles' => $user->roles->pluck('slug')->values()->all()]], 'ip_address' => $request->ip()]);
            }
        });

        return back()->with('status', 'Users and permissions updated.');
    }
}
