<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    public function getUnreadByUser($userId)
    {
        return $this->model->byUser($userId)->unread()->latest()->get();
    }

    public function markAsReadAll($userId)
    {
        return $this->model->byUser($userId)->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
