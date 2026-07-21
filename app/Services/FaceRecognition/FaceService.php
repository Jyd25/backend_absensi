<?php

namespace App\Services\FaceRecognition;

use App\Models\FaceDataset;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FaceService
{
    use ApiResponse;

    public function register(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $employee = Employee::findOrFail($data['employee_id']);

            $existing = FaceDataset::where('employee_id', $employee->id)->where('is_primary', true)->first();

            if ($existing && !isset($data['force'])) {
                return $this->errorResponse('Karyawan sudah memiliki data wajah utama. Gunakan force untuk mengganti.', 422);
            }

            $imagePath = null;
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('faces/' . $employee->id, 'cloudinary');
            }

            $faceDataset = FaceDataset::create([
                'employee_id' => $employee->id,
                'image_path' => $imagePath,
                'descriptor_path' => isset($data['descriptor']) ? json_encode($data['descriptor']) : null,
                'is_primary' => !$existing,
            ]);

            return $faceDataset;
        });
    }

    public function verify(array $data)
    {
        $employeeId = $data['employee_id'];
        $threshold = $data['threshold'] ?? 0.50;

        $primaryDescriptor = FaceDataset::where('employee_id', $employeeId)
            ->where('is_primary', true)
            ->whereNotNull('descriptor_path')
            ->first();

        if (!$primaryDescriptor) {
            return [
                'matched' => true,
                'message' => 'Data wajah belum terdaftar, verifikasi dilewati',
                'score' => 0,
                'no_face_data' => true,
            ];
        }

        $storedDescriptor = json_decode($primaryDescriptor->descriptor_path, true);

        if (!is_array($storedDescriptor) || count($storedDescriptor) < 128) {
            return [
                'matched' => false,
                'message' => 'Data descriptor wajah tidak valid atau belum terdaftar',
                'score' => 0,
                'distance' => 0,
                'threshold' => $threshold,
            ];
        }

        $inputDescriptor = $data['descriptor'];

        if (!is_array($inputDescriptor) || count($inputDescriptor) < 128) {
            return [
                'matched' => false,
                'message' => 'Format descriptor input tidak valid',
                'score' => 0,
                'distance' => 0,
                'threshold' => $threshold,
            ];
        }

        if (count($storedDescriptor) !== count($inputDescriptor)) {
            return [
                'matched' => false,
                'message' => 'Format descriptor tidak cocok',
                'score' => 0,
            ];
        }

        $distance = $this->euclideanDistance($storedDescriptor, $inputDescriptor);
        $score = max(0, 1 - $distance);
        $matched = $distance <= $threshold;

        return [
            'matched' => $matched,
            'message' => $matched ? 'Wajah cocok' : 'Wajah tidak cocok',
            'score' => round($score * 100, 2),
            'distance' => round($distance, 6),
            'threshold' => $threshold,
        ];
    }

    public function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        $count = min(count($a), count($b));

        for ($i = 0; $i < $count; $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    public function getHistory($employeeId = null)
    {
        $query = FaceDataset::with('employee');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query->latest()->paginate(15);
    }

    public function delete($id, $user)
    {
        return DB::transaction(function () use ($id) {
            $faceDataset = FaceDataset::findOrFail($id);

            if ($faceDataset->is_primary) {
                $nextOldest = FaceDataset::where('employee_id', $faceDataset->employee_id)
                    ->where('id', '!=', $id)
                    ->oldest()
                    ->first();

                if ($nextOldest) {
                    $nextOldest->update(['is_primary' => true]);
                }
            }

            if ($faceDataset->image_path) {
                Storage::disk('cloudinary')->delete($faceDataset->image_path);
            }

            $faceDataset->delete();
            return true;
        });
    }
}
