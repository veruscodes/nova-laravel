<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;

class NotificationUnreadController extends Controller
{
    /**
     * Mark the given notification as unread.
     */
    public function __invoke(NotificationRequest $request, string|int $notification): JsonResponse
    {
        $notification = Notification::query()
            ->currentUserFromRequest($request)
            ->findOrFail($notification);

        $notification->update(['read_at' => null]);

        return response()->json();
    }
}
