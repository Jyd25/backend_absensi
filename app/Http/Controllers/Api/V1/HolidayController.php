<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\HolidayService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Holiday::query()->orderBy('date');

        if ($request->filled('month') && $request->filled('year')) {
            $query->forMonth((int) $request->month, (int) $request->year);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->between($request->start_date, $request->end_date);
        }

        if ($request->boolean('paginate', false)) {
            $holidays = $query->paginate($request->get('per_page', 15));

            return $this->paginatedResponse(HolidayResource::collection($holidays));
        }

        return $this->successResponse(
            HolidayResource::collection($query->get())
        );
    }

    public function today(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $reason = HolidayService::nonWorkingReason($today);

        return $this->successResponse([
            'date' => $today->toDateString(),
            'is_non_working_day' => $reason !== null,
            'reason' => $reason['reason'] ?? null,
            'name' => $reason['name'] ?? null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorResponse('Akses ditolak.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date',
            'type' => 'required|in:national,collective',
            'description' => 'nullable|string|max:1000',
        ]);

        $holiday = Holiday::create($validated);

        return $this->successResponse(
            new HolidayResource($holiday),
            'Hari libur berhasil ditambahkan.',
            201
        );
    }

    public function show(Request $request, Holiday $holiday): JsonResponse
    {
        return $this->successResponse(new HolidayResource($holiday));
    }

    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorResponse('Akses ditolak.', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'date' => "sometimes|required|date|unique:holidays,date,{$holiday->id}",
            'type' => 'sometimes|required|in:national,collective',
            'description' => 'nullable|string|max:1000',
        ]);

        $holiday->update($validated);

        return $this->successResponse(
            new HolidayResource($holiday->fresh()),
            'Hari libur berhasil diperbarui.'
        );
    }

    public function destroy(Request $request, Holiday $holiday): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorResponse('Akses ditolak.', 403);
        }

        $holiday->delete();

        return $this->successResponse(null, 'Hari libur berhasil dihapus.');
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role?->name === 'Administrator';
    }
}
