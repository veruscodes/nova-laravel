<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Laravel\Nova\Http\Resources\NotificationResource;
use Laravel\Nova\Notifications\Notification;

/**
 * @param  string|int  $notification
 */
class NotificationRequest extends NovaRequest
{
    /**
     * List latest notification for the user.
     */
    public function notifications(): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            Notification::query()
                ->currentUserFromRequest($this)
                ->latest()
                ->take(100)
                ->get()
        );
    }

    /**
     * Mark notification as read for the user.
     */
    public function markAsRead(): void
    {
        Notification::unread()
            ->currentUserFromRequest($this)
            ->update(['read_at' => now()]);
    }

    /**
     * Notification unread count for the user.
     */
    public function unreadCount(): int
    {
        return Notification::unread()
            ->currentUserFromRequest($this)
            ->count();
    }
}
