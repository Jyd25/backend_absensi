<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->service->getNotifications($request->user()->id, $request);

        return $this->paginatedResponse($notifications);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $userId = request()->user()->id;

        $result = $this->service->markAsRead($notification->id, $userId);

        if (!$result) {
            return $this->errorResponse('Notification not found', 404);
        }

        return $this->successResponse($result, 'Notification marked as read');
    }

    public function markAllAsRead(): JsonResponse
    {
        $this->service->markAllAsRead(request()->user()->id);

        return $this->successResponse(null, 'All notifications marked as read');
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->service->getUnreadCount(request()->user()->id);

        return $this->successResponse(['count' => $count]);
    }
}
