<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FaceDataset;
use App\Models\FaceUpdateRequest;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaceUpdateRequestController extends Controller
{
    use ApiResponse, SendsNotifications;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = FaceUpdateRequest::with(['employee', 'approver']);

        if (in_array($user->role?->name, ['Guru', 'Karyawan'])) {
            $query->where('employee_id', $user->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));
        return $this->paginatedResponse($requests);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'descriptor' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        if (!$user->employee_id) {
            return $this->errorResponse('Akun anda tidak terkait data karyawan', 422);
        }

        $descriptorPath = $request->descriptor;
        if (is_string($request->descriptor)) {
            $decoded = json_decode($request->descriptor, true);
            if (is_array($decoded)) {
                $descriptorPath = json_encode($decoded);
            }
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('face-requests', 'cloudinary');
        }

        $faceRequest = FaceUpdateRequest::create([
            'employee_id' => $user->employee_id,
            'descriptor_path' => $descriptorPath,
            'image_path' => $imagePath,
            'status' => 'pending',
        ]);

        $this->notifyAdmins(
            'Permintaan Update Wajah',
            "{$user->name} mengajukan permintaan update data wajah",
            'info',
            ['face_request_id' => $faceRequest->id, 'employee_id' => $user->employee_id, 'action' => 'create']
        );

        return $this->successResponse($faceRequest, 'Permintaan update wajah berhasil dikirim', 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $faceRequest = FaceUpdateRequest::findOrFail($id);

        $faceRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        DB::transaction(function () use ($faceRequest) {
            FaceDataset::where('employee_id', $faceRequest->employee_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            FaceDataset::create([
                'employee_id' => $faceRequest->employee_id,
                'descriptor_path' => $faceRequest->descriptor_path,
                'image_path' => $faceRequest->image_path,
                'is_primary' => true,
            ]);
        });

        $employee = $faceRequest->employee;
        if ($employee) {
            $empUser = $employee->user ?? \App\Models\User::where('employee_id', $employee->id)->first();
            if ($empUser) {
                $this->notifyUser(
                    $empUser->id,
                    'Update Wajah Disetujui',
                    'Data wajah anda telah berhasil diperbarui.',
                    'success',
                    ['face_request_id' => $faceRequest->id, 'action' => 'approved']
                );
            }
        }

        return $this->successResponse($faceRequest->fresh(['employee', 'approver']), 'Permintaan disetujui');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $faceRequest = FaceUpdateRequest::findOrFail($id);
        $request->validate(['admin_note' => 'required|string']);

        $faceRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'admin_note' => $request->admin_note,
        ]);

        $employee = $faceRequest->employee;
        if ($employee) {
            $empUser = $employee->user ?? \App\Models\User::where('employee_id', $employee->id)->first();
            if ($empUser) {
                $this->notifyUser(
                    $empUser->id,
                    'Update Wajah Ditolak',
                    "Permintaan update wajah ditolak. Alasan: {$request->admin_note}",
                    'warning',
                    ['face_request_id' => $faceRequest->id, 'action' => 'rejected']
                );
            }
        }

        return $this->successResponse($faceRequest->fresh(['employee', 'approver']), 'Permintaan ditolak');
    }
}
