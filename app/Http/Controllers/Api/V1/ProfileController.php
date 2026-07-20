<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'employee.department', 'employee.position', 'employee.schedule']);
        return $this->successResponse($user);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($user, $validated, &$result) {
            $userData = array_intersect_key($validated, array_flip(['name', 'email']));
            if (!empty($userData)) {
                $user->update($userData);
            }

            if ($user->employee_id) {
                $employeeData = array_intersect_key($validated, array_flip(['phone', 'address', 'birth_place', 'birth_date', 'photo']));
                if (!empty($employeeData)) {
                    if (isset($employeeData['photo']) && $request->hasFile('photo')) {
                        $employeeData['photo'] = $request->file('photo')->store('profiles', 'cloudinary');
                    }
                    Employee::where('id', $user->employee_id)->update($employeeData);
                }
            }
        });

        return $this->successResponse(
            $user->fresh(['role', 'employee.department', 'employee.position', 'employee.schedule']),
            'Profil berhasil diperbarui'
        );
    }
}
