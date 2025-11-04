<?php

namespace Laravel\Nova\Notifications;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Nova\Http\Requests\NovaRequest;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nova_notifications';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The guarded attributes on the model.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    public $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Return the notifiable relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope the given query by unread notifications.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include current authenticated user.
     */
    public function scopeCurrentUser(Builder $query): Builder
    {
        return $this->scopeCurrentUserFromRequest($query, app(NovaRequest::class));
    }

    /**
     * Scope a query to only include current authenticated user from request.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function scopeCurrentUserFromRequest(Builder $query, NovaRequest $request): Builder
    {
        $user = $request->user();

        if (\is_null($user)) {
            throw (new ModelNotFoundException)->setModel(static::class);
        }

        return $query->where(function ($query) use ($user) {
            return $query->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey());
        });
    }
}
