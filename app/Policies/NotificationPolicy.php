<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Governs access to Laravel's built-in DatabaseNotification model.
 * Every user sees only notifications addressed to them: there is no
 * viewAny — NotificationController always scopes the index query to
 * $user->notifications() — only a per-notification ownership check for
 * marking one as read.
 */
class NotificationPolicy
{
    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === User::class
            && (int) $notification->notifiable_id === $user->id;
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
