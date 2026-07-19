<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = LeaveRequest::with(['employee', 'approver']);

        if (in_array($user->role?->name, ['Guru', 'Karyawan'])) {
            $query->where('employee_id', $user->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $term = $request->search;
            $query->whereHas('employee', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%");
            });
        }

        $leaves = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:permission,sick,leave',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
        ]);

        $user = $request->user();
        if (!$user->employee_id) {
            return $this->errorResponse('Akun anda tidak terkait data karyawan', 422);
        }

        $leave = LeaveRequest::create([
            'employee_id' => $user->employee_id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return $this->successResponse($leave, 'Pengajuan izin berhasil dikirim', 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        $leave->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'admin_note' => $request->admin_note,
        ]);

        return $this->successResponse($leave->fresh(['employee', 'approver']), 'Izin disetujui');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        $request->validate([
            'admin_note' => 'required|string',
        ]);

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'admin_note' => $request->admin_note,
        ]);

        return $this->successResponse($leave->fresh(['employee', 'approver']), 'Izin ditolak');
    }

    public function destroy(int $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        if ($leave->status !== 'pending') {
            return $this->errorResponse('Hanya pengajuan pending yang bisa dihapus', 422);
        }
        $leave->delete();
        return $this->successResponse(null, 'Pengajuan izin dihapus');
    }
}
