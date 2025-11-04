<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;

class NotificationDeleteAllController extends Controller
{
    /**
     * Delete all notifications.
     */
    public function __invoke(NotificationRequest $request): JsonResponse
    {
        Notification::query()
            ->currentUserFromRequest($request)
            ->delete();

        return response()->json();
    }
}
