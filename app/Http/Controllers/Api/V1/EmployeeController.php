<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponse, SendsNotifications;

    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::with(['department', 'position', 'schedule']);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse(EmployeeResource::collection($employees));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $employee = $this->employeeService->createWithUser($request->validated());

        $this->notifyAdmins(
            'Karyawan Baru',
            "Karyawan baru ditambahkan: {$employee->name} ({$employee->nik})",
            'info',
            ['employee_id' => $employee->id, 'action' => 'create']
        );

        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee created successfully.',
            201
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'position', 'schedule', 'user']);

        return $this->successResponse(
            new EmployeeResource($employee)
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee = $this->employeeService->update($employee->id, $request->validated());

        $this->notifyAdmins(
            'Karyawan Diubah',
            "Data karyawan diubah: {$employee->name} ({$employee->nik})",
            'info',
            ['employee_id' => $employee->id, 'action' => 'update']
        );

        return $this->successResponse(
            new EmployeeResource($employee->load(['department', 'position', 'schedule'])),
            'Employee updated successfully.'
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $name = $employee->name;
        $nik = $employee->nik;
        $employee->delete();

        $this->notifyAdmins(
            'Karyawan Dihapus',
            "Karyawan dihapus: {$name} ({$nik})",
            'warning',
            ['action' => 'delete']
        );

        return $this->successResponse(null, 'Employee deleted successfully.');
    }
}
