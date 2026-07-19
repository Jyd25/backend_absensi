<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\RegisterFaceRequest;
use App\Http\Requests\Face\VerifyFaceRequest;
use App\Http\Resources\FaceDatasetResource;
use App\Services\FaceRecognition\FaceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FaceService $faceService
    ) {}

    public function register(RegisterFaceRequest $request): JsonResponse
    {
        $result = $this->faceService->register($request->validated(), $request->user());

        if (!$result) {
            return $this->errorResponse('Gagal mendaftarkan wajah', 500);
        }

        if (is_array($result) && isset($result['success']) && !$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse(
            new FaceDatasetResource($result),
            'Wajah berhasil didaftarkan',
            201
        );
    }

    public function verify(VerifyFaceRequest $request): JsonResponse
    {
        $result = $this->faceService->verify($request->validated());

        return $this->successResponse($result, $result['message']);
    }

    public function history(Request $request): JsonResponse
    {
        $history = $this->faceService->getHistory($request->employee_id);
        return $this->paginatedResponse($history, 'Riwayat face dataset berhasil dimuat');
    }

    public function destroy($id, Request $request): JsonResponse
    {
        $this->faceService->delete($id, $request->user());
        return $this->successResponse(null, 'Face dataset berhasil dihapus');
    }
}
