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

            $imageData = null;
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $binary = file_get_contents($data['image']->getRealPath());
                $mimeType = $data['image']->getMimeType();
                $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($binary);
            }

            $faceDataset = FaceDataset::create([
                'employee_id' => $employee->id,
                'image_data' => $imageData,
                'image_path' => $imageData,
                'descriptor_path' => is_array($data['descriptor']) ? json_encode($data['descriptor']) : $data['descriptor'],
                'is_primary' => !$existing,
            ]);

            return $faceDataset;
        });
    }

    public function verify(array $data)
    {
        $employeeId = $data['employee_id'];
        $threshold = $data['threshold'] ?? 0.50;

        $faceDatasets = FaceDataset::where('employee_id', $employeeId)
            ->whereNotNull('descriptor_path')
            ->get();

        if ($faceDatasets->isEmpty()) {
            return [
                'matched' => true,
                'message' => 'Data wajah belum terdaftar, verifikasi dilewati',
                'score' => 0,
                'no_face_data' => true,
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

        $bestDistance = PHP_INT_MAX;
        $bestScore = 0;
        $matched = false;

        foreach ($faceDatasets as $dataset) {
            $storedDescriptor = json_decode($dataset->descriptor_path, true);
            if (!is_array($storedDescriptor) || count($storedDescriptor) < 128) {
                continue;
            }
            if (count($storedDescriptor) !== count($inputDescriptor)) {
                continue;
            }

            $distance = $this->euclideanDistance($storedDescriptor, $inputDescriptor);
            $score = max(0, 1 - $distance);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestScore = $score;
            }

            if ($distance <= $threshold) {
                $matched = true;
                break;
            }
        }

        return [
            'matched' => $matched,
            'message' => $matched ? 'Wajah cocok' : 'Wajah tidak cocok',
            'score' => round($bestScore * 100, 2),
            'distance' => round($bestDistance, 6),
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

            if ($faceDataset->image_path && !str_starts_with($faceDataset->image_path, 'data:')) {
                Storage::disk('local')->delete($faceDataset->image_path);
            }

            $faceDataset->delete();
            return true;
        });
    }
}
