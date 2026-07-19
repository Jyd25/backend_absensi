<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\Auth\TokenResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 401);
        }

        $userResource = new UserResource($result['data']['user']);
        $tokenData = [
            'access_token' => $result['data']['token'],
            'refresh_token' => $result['data']['token'],
            'expires_in' => $result['data']['expires_in'],
            'token_type' => 'Bearer',
        ];

        return $this->successResponse([
            'user' => $userResource,
            'token' => new TokenResource($tokenData),
        ], $result['message'], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 500);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 500);
        }

        $tokenData = [
            'access_token' => $result['data']['token'],
            'refresh_token' => $result['data']['token'],
            'expires_in' => $result['data']['expires_in'],
            'token_type' => 'Bearer',
        ];

        return $this->successResponse([
            'token' => new TokenResource($tokenData),
        ], $result['message']);
    }

    public function profile(Request $request): JsonResponse
    {
        $result = $this->authService->getProfile($request->user()->id);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 404);
        }

        return $this->successResponse(
            new UserResource($result['data']),
            $result['message']
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $result = $this->authService->changePassword($request->user(), $request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->successResponse(
            new UserResource($user->fresh(['role', 'employee.department', 'employee.position', 'employee.schedule'])),
            'Profile updated successfully.'
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->forgotPassword($request->email);

        return $this->successResponse($result['data'] ?? null, $result['message']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->getProfile($request->user()->id);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 404);
        }

        return $this->successResponse(
            new UserResource($result['data']),
            $result['message']
        );
    }

    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'authenticated' => true,
            'user' => new UserResource($user),
        ], 'Token is valid.');
    }
}
