<?php

namespace App\Services\Auth;

use App\Enums\LogStatus;
use App\Events\LoginFailed;
use App\Events\PasswordChanged;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Repositories\Auth\AuthRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService extends BaseService
{
    protected AuthRepository $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        parent::__construct($authRepository);
        $this->authRepository = $authRepository;
    }

    public function login(LoginRequest $request): array
    {
        $credentials = $request->only('email', 'password');

        $user = $this->authRepository->findByEmail($request->email);

        if (!$user || !Hash::check($request->password, $user->password)) {
            event(new LoginFailed($request->email));

            $this->authRepository->createLoginLog([
                'user_id' => $user?->id,
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => LogStatus::Failed,
                'message' => 'Invalid credentials.',
            ]);

            return [
                'success' => false,
                'message' => 'Invalid credentials.',
            ];
        }

        if ($user->status->value !== 'active') {
            event(new LoginFailed($request->email));

            $this->authRepository->createLoginLog([
                'user_id' => $user->id,
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => LogStatus::Failed,
                'message' => 'Account is not active.',
            ]);

            return [
                'success' => false,
                'message' => 'Your account is not active. Please contact administrator.',
            ];
        }

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            event(new LoginFailed($request->email));

            return [
                'success' => false,
                'message' => 'Invalid credentials.',
            ];
        }

        $this->authRepository->createLoginLog([
            'user_id' => $user->id,
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => LogStatus::Success,
            'message' => 'Login successful.',
        ]);

        event(new UserLoggedIn($user));

        $user->load(['role', 'employee.department', 'employee.position', 'employee.schedule']);

        return [
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ],
        ];
    }

    public function logout(User $user): array
    {
        try {
            JWTAuth::parseToken()->invalidate();

            event(new UserLoggedOut($user));

            return [
                'success' => true,
                'message' => 'Logged out successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to logout.',
            ];
        }
    }

    public function refresh(User $user): array
    {
        try {
            $token = JWTAuth::parseToken()->refresh();

            return [
                'success' => true,
                'message' => 'Token refreshed successfully.',
                'data' => [
                    'token' => $token,
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to refresh token.',
            ];
        }
    }

    public function getProfile(int $userId): array
    {
        $user = $this->authRepository->getProfile($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => $user,
        ];
    }

    public function changePassword(User $user, array $data): array
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        event(new PasswordChanged($user));

        return [
            'success' => true,
            'message' => 'Password changed successfully.',
        ];
    }

    public function forgotPassword(string $email): array
    {
        $user = $this->authRepository->findByEmail($email);

        if (!$user) {
            return [
                'success' => true,
                'message' => 'If the email exists, a reset link has been sent.',
            ];
        }

        $token = Str::random(64);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        return [
            'success' => true,
            'message' => 'If the email exists, a reset link has been sent.',
            'data' => [
                'token' => $token,
                'email' => $email,
            ],
        ];
    }

    public function resetPassword(array $data): array
    {
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (!$resetRecord || !Hash::check($data['token'], $resetRecord->token)) {
            return [
                'success' => false,
                'message' => 'Invalid reset token.',
            ];
        }

        if (now()->parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            return [
                'success' => false,
                'message' => 'Reset token has expired.',
            ];
        }

        $user = $this->authRepository->findByEmail($data['email']);

        if ($user) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        \DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->delete();

        return [
            'success' => true,
            'message' => 'Password has been reset successfully.',
        ];
    }
}
