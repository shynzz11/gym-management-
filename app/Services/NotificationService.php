<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a new notification.
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        ?string $description = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Get notifications for a specific user.
     */
    public function getForUser(
        int $userId,
        int $limit = 10,
        bool $unreadOnly = false
    ): Collection {
        $customQuery = Notification::forUser($userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $customQuery->unread();
        }

        $customNotifications = $customQuery->limit($limit)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'description' => $n->description,
                'data' => $n->data,
                'action_url' => $n->action_url,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'is_custom' => true,
            ];
        });

        $user = User::find($userId);
        if (! $user) {
            return collect($customNotifications);
        }

        $standardQuery = $user->notifications();
        if ($unreadOnly) {
            $standardQuery->whereNull('read_at');
        }

        $standardNotifications = $standardQuery->limit($limit)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'info',
                'title' => $n->data['title'] ?? 'Notification',
                'description' => $n->data['description'] ?? ($n->data['message'] ?? ''),
                'data' => $n->data,
                'action_url' => $n->data['action_url'] ?? null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'is_custom' => false,
            ];
        });

        return $customNotifications->concat($standardNotifications)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Get all notifications for a user with pagination approximation.
     */
    public function getAllForUser(int $userId, int $perPage = 20)
    {
        return $this->getForUser($userId, $perPage * 5);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string|int $notificationId): bool
    {
        if (is_numeric($notificationId)) {
            $notification = Notification::find($notificationId);
            if ($notification) {
                return $notification->markAsRead();
            }
        }

        $standardNotification = DatabaseNotification::find($notificationId);
        if ($standardNotification) {
            $standardNotification->markAsRead();

            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        $customCount = Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);

        $user = User::find($userId);
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return $customCount;
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $customCount = Notification::forUser($userId)
            ->unread()
            ->count();

        $user = User::find($userId);
        $standardCount = $user ? $user->unreadNotifications()->count() : 0;

        return $customCount + $standardCount;
    }

    /**
     * Mark all expiry notifications as read for a user.
     */
    public function markExpiryNotificationsAsRead(int $userId): void
    {
        // Custom notifications
        Notification::forUser($userId)
            ->where('title', 'like', '%Membership Expiring%')
            ->unread()
            ->update(['read_at' => now()]);

        // Standard Laravel notifications
        $user = User::find($userId);
        if ($user) {
            $user->unreadNotifications()
                ->where('data->title', 'like', '%Membership Expiring%')
                ->get()
                ->each->markAsRead();
        }
    }

    /**
     * Delete a notification.
     */
    public function delete(string|int $notificationId): bool
    {
        if (is_numeric($notificationId)) {
            $notification = Notification::find($notificationId);
            if ($notification) {
                return $notification->delete();
            }
        }

        $standardNotification = DatabaseNotification::find($notificationId);
        if ($standardNotification) {
            return $standardNotification->delete();
        }

        return false;
    }

    /**
     * Delete all notifications for a user.
     */
    public function deleteAllForUser(int $userId): int
    {
        $customCount = Notification::forUser($userId)->delete();

        $user = User::find($userId);
        if ($user) {
            $user->notifications()->delete();
        }

        return $customCount;
    }

    /**
     * Create a success notification.
     */
    public function success(int $userId, string $title, ?string $description = null, ?array $data = null, ?string $actionUrl = null): Notification
    {
        return $this->create($userId, 'success', $title, $description, $data, $actionUrl);
    }

    /**
     * Create an info notification.
     */
    public function info(int $userId, string $title, ?string $description = null, ?array $data = null, ?string $actionUrl = null): Notification
    {
        return $this->create($userId, 'info', $title, $description, $data, $actionUrl);
    }

    /**
     * Create a warning notification.
     */
    public function warning(int $userId, string $title, ?string $description = null, ?array $data = null, ?string $actionUrl = null): Notification
    {
        return $this->create($userId, 'warning', $title, $description, $data, $actionUrl);
    }

    /**
     * Create an error notification.
     */
    public function error(int $userId, string $title, ?string $description = null, ?array $data = null, ?string $actionUrl = null): Notification
    {
        return $this->create($userId, 'error', $title, $description, $data, $actionUrl);
    }
}
