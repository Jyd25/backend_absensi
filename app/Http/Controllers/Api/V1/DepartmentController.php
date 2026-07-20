<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use ApiResponse, SendsNotifications;

    public function __construct(
        protected DepartmentService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        if ($search) {
            $departments = $this->service->search($search)->withCount('employees')->paginate($perPage);
        } else {
            $departments = $this->service->paginate($perPage);
            $departments->loadCount('employees');
        }

        return $this->paginatedResponse(DepartmentResource::collection($departments));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $department = $this->service->create($request->validated());

        $this->notifyAdmins(
            'Departemen Baru',
            "Departemen baru ditambahkan: {$department->name}",
            'info',
            ['department_id' => $department->id, 'action' => 'create']
        );

        return $this->successResponse(
            new DepartmentResource($department->loadCount('employees')),
            'Department created successfully',
            201
        );
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return $this->successResponse(
            new DepartmentResource($department->loadCount('employees'))
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $department = $this->service->update($department->id, $request->validated());

        $this->notifyAdmins(
            'Departemen Diubah',
            "Data departemen diubah: {$department->name}",
            'info',
            ['department_id' => $department->id, 'action' => 'update']
        );

        return $this->successResponse(
            new DepartmentResource($department->loadCount('employees')),
            'Department updated successfully'
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $name = $department->name;
        $this->service->delete($department->id);

        $this->notifyAdmins(
            'Departemen Dihapus',
            "Departemen dihapus: {$name}",
            'warning',
            ['action' => 'delete']
        );

        return $this->successResponse(null, 'Department deleted successfully');
    }
}
