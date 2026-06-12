<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use App\TenantStatus;
use App\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function ensureTenantContext(Request $request): void
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        if (TenantContext::getId() !== null || TenantContext::isBypassed()) {
            return;
        }

        $user = $request->user();

        if ($user?->hasRole('super-admin') && $request->hasSession()) {
            try {
                $activeTenantId = $request->session()->get('active_tenant_id');

                if ($activeTenantId !== null) {
                    TenantContext::setId((int) $activeTenantId);

                    return;
                }
            } catch (\RuntimeException) {
                //
            }
        }

        if ($user?->hasRole('super-admin') && TenantContext::getId() === null) {
            $activeTenantCount = Tenant::query()
                ->where('status', TenantStatus::Active)
                ->count();

            if ($activeTenantCount === 1) {
                $tenantId = Tenant::query()
                    ->where('status', TenantStatus::Active)
                    ->orderBy('id')
                    ->value('id');

                if ($tenantId !== null) {
                    TenantContext::setId((int) $tenantId);
                }
            }
        }
    }

    private function currentTenantId(Request $request): int
    {
        $this->ensureTenantContext($request);

        $tenantId = TenantContext::getId() ?? $request->user()?->tenant_id;

        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }

    /**
     * @return Builder<User>
     */
    private function tenantUsersQuery(Request $request): Builder
    {
        return User::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = $this->tenantUsersQuery($request)
            ->with('roles')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => $this->transformUser($user));

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'can' => [
                'create' => $request->user()?->can('users.create') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureTenantContext($request);
        $this->authorize('create', User::class);

        return Inertia::render('admin/users/create', [
            'roles' => $this->assignableRoleOptions($request->user()),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $tenantId = $this->currentTenantId($request);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
            'status' => UserStatus::Active,
        ]);

        $user->forceFill(['tenant_id' => $tenantId])->save();

        $user->assignRole($request->validated('role'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created successfully.')]);

        return to_route('admin.users.index');
    }

    public function edit(Request $request, int $managedUser): Response
    {
        $managedUser = $this->tenantUsersQuery($request)->findOrFail($managedUser);

        $this->authorize('update', $managedUser);

        $managedUser->load('roles');

        return Inertia::render('admin/users/edit', [
            'user' => $this->transformUser($managedUser),
            'roles' => $this->assignableRoleOptions($request->user(), $managedUser),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, int $managedUser): RedirectResponse
    {
        $managedUser = $this->tenantUsersQuery($request)->findOrFail($managedUser);

        $managedUser->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'status' => $request->validated('status'),
        ]);

        if ($password = $request->validated('password')) {
            $managedUser->password = Hash::make($password);
        }

        $managedUser->save();

        if ($request->user()?->can('users.assign-roles')) {
            $managedUser->syncRoles([$request->validated('role')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated successfully.')]);

        return to_route('admin.users.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status->value,
            'role' => $user->roles->first()?->name,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    private function assignableRoleOptions(?User $actor, ?User $managedUser = null): array
    {
        $roleNames = match (true) {
            $managedUser?->hasRole('super-admin') => ['super-admin'],
            $actor?->hasRole('super-admin') => ['supplier-admin', 'delivery-agent', 'customer'],
            default => ['delivery-agent', 'customer'],
        };

        return Role::query()
            ->whereIn('name', $roleNames)
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'name' => $role->name,
                'label' => str($role->name)->headline()->toString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(UserStatus::cases())
            ->map(fn (UserStatus $status): array => [
                'value' => $status->value,
                'label' => str($status->value)->headline()->toString(),
            ])
            ->values()
            ->all();
    }
}
