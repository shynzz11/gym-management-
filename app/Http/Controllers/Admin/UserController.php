<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected SubscriptionService $subscriptionService,
    ) {}

    public function index(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        return inertia('Admin/Members/Index', [
            'users' => $this->userService->getAll([
                ...request()->only('search'),
                'directory' => 'staff',
            ]),
            'directoryType' => 'staff',
            'plans' => [],
            'customerIndexUrl' => route('admin.customers.index'),
            'roles' => Role::all(),
        ]);
    }

    public function customers(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        $users = $this->userService->getAll([
            ...request()->only('search'),
            'directory' => 'customer',
        ]);

        $users->getCollection()->load('subscriptions.plan');

        return inertia('Admin/Members/Index', [
            'users' => $users,
            'directoryType' => 'customer',
            'plans' => Plan::query()
                ->where('is_active', true)
                ->select([
                    'id',
                    'name',
                    'price',
                    'duration_days',
                    'description',
                    'facilities',
                    'access_type',
                    'start_time',
                    'end_time',
                    'is_offer',
                    'offer_price',
                    'offer_ends_at',
                ])
                ->latest('id')
                ->get(),
            'customerIndexUrl' => route('admin.customers.index'),
            'roles' => Role::all(),
        ]);
    }

    public function create(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        return inertia('Admin/Members/Create', [
            'directoryType' => 'staff',
            'plans' => [],
            'roles' => Role::all(),
        ]);
    }

    public function createCustomer(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        return inertia('Admin/Members/Create', [
            'directoryType' => 'customer',
            'plans' => Plan::query()
                ->where('is_active', true)
                ->select([
                    'id',
                    'name',
                    'price',
                    'duration_days',
                    'description',
                    'facilities',
                    'access_type',
                    'start_time',
                    'end_time',
                    'is_offer',
                    'offer_price',
                    'offer_ends_at',
                ])
                ->latest('id')
                ->get(),
            'roles' => Role::all(),
        ]);
    }

    public function store(StoreUserRequest $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        $data = $request->validated();
        $data['type'] = UserType::ADMIN->value;

        if (isset($data['role_id'])) {
            $data['roles'] = [$data['role_id']];
            unset($data['role_id']);
        }

        $this->userService->create($data);

        return to_route('admin.staff.index')->with('success', 'User created successfully.');
    }

    public function storeCustomer(StoreUserRequest $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        $data = $request->validated();
        $data['type'] = UserType::CUSTOMER->value;
        $data['password'] = \Illuminate\Support\Str::random(40);
        $selectedPlanId = $data['plan_id'] ?? null;
        unset($data['role_id']);
        unset($data['plan_id']);

        $customer = $this->userService->create($data);

        if ($selectedPlanId) {
            $this->subscriptionService->create([
                'user_id' => $customer->id,
                'plan_id' => $selectedPlanId,
                'status' => 'active',
            ]);
        }

        return to_route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(User $user): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if ($user->isSuperAdmin()) {
            abort(403, 'The Super Admin account is immutable.');
        }

        if ($user->isCustomer()) {
            abort(404);
        }

        $user->load(['roles', 'customerProfile']);
        $userData = $user->toArray();
        $userData['role_id'] = $user->roles->first()?->id ?? '';

        return inertia('Admin/Members/Edit', [
            'directoryType' => 'staff',
            'user' => $userData,
            'roles' => Role::all(),
        ]);
    }

    public function editCustomer(User $user): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if (! $user->isCustomer()) {
            abort(404);
        }

        $user->load(['roles', 'customerProfile', 'subscriptions.plan']);
        $userData = $user->toArray();
        $userData['role_id'] = $user->roles->first()?->id ?? '';
        $activeSubscriptions = $user->subscriptions
            ->whereIn('status', ['active', 'pending'])
            ->sortByDesc('end_date')
            ->values();

        return inertia('Admin/Members/Edit', [
            'directoryType' => 'customer',
            'user' => $userData,
            'activeSubscriptions' => $activeSubscriptions,
            'roles' => Role::all(),
        ]);
    }

    public function update(\App\Http\Requests\Admin\UpdateUserRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if ($user->isSuperAdmin()) {
            abort(403, 'The Super Admin account is immutable.');
        }

        if ($user->isCustomer()) {
            abort(404);
        }

        $validated = $request->validated();

        if (isset($validated['role_id'])) {
            $validated['roles'] = [$validated['role_id']];
            unset($validated['role_id']);
        }

        // Remove password_confirmation
        unset($validated['password_confirmation']);

        $this->userService->update($user, $validated);

        return to_route($user->isCustomer() ? 'admin.customers.index' : 'admin.staff.index')
            ->with('success', 'User updated successfully.');
    }

    public function updateCustomer(\App\Http\Requests\Admin\UpdateUserRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if (! $user->isCustomer()) {
            abort(404);
        }

        $validated = $request->validated();
        unset($validated['role_id']);
        unset($validated['password_confirmation']);

        $this->userService->update($user, $validated);

        return to_route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function updateStatus(\App\Http\Requests\Admin\UpdateUserStatusRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if ($user->isSuperAdmin()) {
            abort(403, 'The Super Admin account is immutable.');
        }

        $this->userService->updateStatus($user, $request->validated()['status']);

        return back()->with('success', 'User status updated successfully.');
    }

    public function destroy(User $user): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::MEMBER_DIRECTORY);

        if ($user->isSuperAdmin()) {
            abort(403, 'The Super Admin account is immutable.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $this->userService->delete($user);

        return back()->with('success', 'User deleted successfully.');
    }
}
