<?php

namespace Laravel\Nova\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class NovaChannel
{
    /**
     * Send channel notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function send($notifiable, LaravelNotification $notification)
    {
        if ($this->canRun($notifiable) && method_exists($notification, 'toNova')) {
            $payload = $notification->toNova($notifiable);

            Notification::create([
                'id' => Str::orderedUuid(),
                'type' => $notification::class,
                'notifiable_id' => $notifiable->getKey(),
                'notifiable_type' => $notifiable->getMorphClass(),
                'data' => $payload instanceof Arrayable ? $payload->toArray() : $payload,
            ]);
        }
    }

    /**
     * Determine if notification should be send to $notifiable.
     *
     * @param  mixed  $notifiable
     */
    protected function canRun($notifiable): bool
    {
        return app()->environment('local') || Gate::forUser($notifiable)->check('viewNova');
    }
}
