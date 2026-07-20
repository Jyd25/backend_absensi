<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Position\StorePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Services\PositionService;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use ApiResponse, SendsNotifications;

    public function __construct(
        protected PositionService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        if ($search) {
            $positions = $this->service->search($search)->with('role')->withCount('employees')->paginate($perPage);
        } else {
            $positions = $this->service->paginate($perPage);
            $positions->load('role')->loadCount('employees');
        }

        return $this->paginatedResponse(PositionResource::collection($positions));
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = $this->service->create($request->validated());

        $this->notifyAdmins(
            'Jabatan Baru',
            "Jabatan baru ditambahkan: {$position->name}",
            'info',
            ['position_id' => $position->id, 'action' => 'create']
        );

        return $this->successResponse(
            new PositionResource($position->load('role')->loadCount('employees')),
            'Position created successfully',
            201
        );
    }

    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        return $this->successResponse(
            new PositionResource($position->load('role')->loadCount('employees'))
        );
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);

        $position = $this->service->update($position->id, $request->validated());

        $this->notifyAdmins(
            'Jabatan Diubah',
            "Data jabatan diubah: {$position->name}",
            'info',
            ['position_id' => $position->id, 'action' => 'update']
        );

        return $this->successResponse(
            new PositionResource($position->load('role')->loadCount('employees')),
            'Position updated successfully'
        );
    }

    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('delete', $position);

        $name = $position->name;
        $this->service->delete($position->id);

        $this->notifyAdmins(
            'Jabatan Dihapus',
            "Jabatan dihapus: {$name}",
            'warning',
            ['action' => 'delete']
        );

        return $this->successResponse(null, 'Position deleted successfully');
    }
}
