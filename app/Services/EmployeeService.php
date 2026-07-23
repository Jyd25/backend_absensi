<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeService extends BaseService
{
    protected EmployeeRepository $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        parent::__construct($employeeRepository);
        $this->employeeRepository = $employeeRepository;
    }

    public function search($term)
    {
        return $this->employeeRepository->search($term);
    }

    public function getActive()
    {
        return $this->employeeRepository->getActive();
    }

    public function createWithUser(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = $this->employeeRepository->create($data);

            $position = $employee->position;
            $role = $position && $position->role_id
                ? Role::find($position->role_id)
                : Role::where('name', 'Karyawan')->first();

            $password = $data['user_password'] ?? Str::random(8);

            $user = User::create([
                'role_id' => $role?->id,
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => Hash::make($password),
                'status' => 'active',
            ]);

            if ($role) {
                $user->assignRole($role->name);
            }

            return $employee->load(['department', 'position', 'schedule', 'user']);
        });
    }

    public function update($id, array $data): Employee
    {
        $employee = $this->employeeRepository->findOrFail($data);
        $positionChanged = isset($data['position_id']) && $data['position_id'] != $employee->position_id;

        $employee = $this->employeeRepository->update($employee, $data);

        if ($positionChanged && $employee->user) {
            $position = $employee->position;
            $role = $position && $position->role_id
                ? Role::find($position->role_id)
                : Role::where('name', 'Karyawan')->first();

            if ($role) {
                $employee->user->update(['role_id' => $role->id]);
                $employee->user->syncRoles([$role->name]);
            }
        }

        return $employee->load(['department', 'position', 'schedule', 'user']);
    }
}
