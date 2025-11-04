<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;

class NotificationReadAllController extends Controller
{
    /**
     * Mark the given notification as read.
     */
    public function __invoke(NotificationRequest $request): JsonResponse
    {
        $request->markAsRead();

        return response()->json();
    }
}
