<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::with(['role', 'employee']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role_id') && $request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse(UserResource::collection($users));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|string|in:active,inactive,suspended',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'employee_id' => $request->employee_id,
            'status' => $request->get('status', 'active'),
        ]);

        $role = Role::find($request->role_id);
        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return $this->successResponse(
            new UserResource($user->load(['role', 'employee'])),
            'User created successfully.',
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        return $this->successResponse(
            new UserResource($user->load(['role', 'employee']))
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|string|in:active,inactive,suspended',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'employee_id' => $request->employee_id,
            'status' => $request->get('status', $user->status),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $role = Role::find($request->role_id);
        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return $this->successResponse(
            new UserResource($user->fresh()->load(['role', 'employee'])),
            'User updated successfully.'
        );
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isAdmin) {
            return $this->errorResponse('Cannot delete administrator user.', 403);
        }

        $user->syncRoles([]);
        $user->delete();

        return $this->successResponse(null, 'User deleted successfully.');
    }

    public function roles(): JsonResponse
    {
        $roles = Role::all(['id', 'name']);

        return $this->successResponse($roles);
    }
}
