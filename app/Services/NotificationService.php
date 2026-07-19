<?php

namespace App\Services;

use App\Repositories\NotificationRepository;

class NotificationService extends BaseService
{
    public function __construct(NotificationRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getNotifications($userId, $request)
    {
        $perPage = $request->get('per_page', 15);

        return $this->repository->query()
            ->byUser($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead($notificationId, $userId)
    {
        $notification = $this->repository->query()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return null;
        }

        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllAsRead($userId)
    {
        return $this->repository->markAsReadAll($userId);
    }

    public function getUnreadCount($userId)
    {
        return $this->repository->query()
            ->byUser($userId)
            ->unread()
            ->count();
    }
}
