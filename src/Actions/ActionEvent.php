<?php

namespace Laravel\Nova\Actions;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;
use Throwable;

use function Orchestra\Sidekick\Eloquent\model_state;

/**
 * @property \Illuminate\Database\Eloquent\Model $target
 * @property \Illuminate\Foundation\Auth\User $user
 * @property array|null $changes
 * @property array|null $original
 */
class ActionEvent extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changes' => 'array',
        'original' => 'array',
    ];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Get the user that initiated the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Util::userModelOrFallback(), 'user_id');
    }

    /**
     * Get the target of the action for user interface linking.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function target()
    {
        $queryWithTrashed = static function ($query) {
            return $query->withTrashed();
        };

        return $this->morphTo('target', 'target_type', 'target_id')
                    ->constrain(
                        collect(Nova::$resources)
                            ->filter(static fn ($resource) => $resource::softDeletes())
                            ->mapWithKeys(static fn ($resource) => [$resource::$model => $queryWithTrashed])
                            ->all()
                    )->when(true, static fn ($query) => $query->hasMacro('withTrashed') ? $queryWithTrashed($query) : $query);
    }

    /**
     * Create a new action event instance for a resource creation.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return static
     */
    public static function forResourceCreate($user, $model)
    {
        [$original, $changes] = model_state($model);

        return new static([
            'batch_id' => (string) Str::orderedUuid(),
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'Create',
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'original' => $original,
            'changes' => $changes,
            'status' => 'finished',
            'exception' => '',
        ]);
    }

    /**
     * Create a new action event instance for a resource update.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return static
     */
    public static function forResourceUpdate($user, $model)
    {
        [$original, $changes] = model_state($model);

        return new static([
            'batch_id' => (string) Str::orderedUuid(),
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'Update',
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'changes' => $changes,
            'original' => $original,
            'status' => 'finished',
            'exception' => '',
        ]);
    }

    /**
     * Create a new action event instance for an attached resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  \Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     * @return static
     */
    public static function forAttachedResource(NovaRequest $request, $parent, $pivot)
    {
        [$original, $changes] = model_state($pivot);

        return new static([
            'batch_id' => (string) Str::orderedUuid(),
            'user_id' => Nova::user($request)->getAuthIdentifier(),
            'name' => 'Attach',
            'actionable_type' => $parent->getMorphClass(),
            'actionable_id' => $parent->getKey(),
            'target_type' => Nova::modelInstanceForKey($request->relatedResource)->getMorphClass(),
            'target_id' => $request->input($request->relatedResource),
            'model_type' => $pivot->getMorphClass(),
            'model_id' => $pivot->getKey(),
            'fields' => '',
            'original' => $original,
            'changes' => $changes,
            'status' => 'finished',
            'exception' => '',
        ]);
    }

    /**
     * Create a new action event instance for an attached resource update.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  \Illuminate\Database\Eloquent\Relations\Pivot  $pivot
     * @return static
     */
    public static function forAttachedResourceUpdate(NovaRequest $request, $parent, $pivot)
    {
        [$original, $changes] = model_state($pivot);

        return new static([
            'batch_id' => (string) Str::orderedUuid(),
            'user_id' => Nova::user($request)->getAuthIdentifier(),
            'name' => 'Update Attached',
            'actionable_type' => $parent->getMorphClass(),
            'actionable_id' => $parent->getKey(),
            'target_type' => Nova::modelInstanceForKey($request->relatedResource)->getMorphClass(),
            'target_id' => $request->relatedResourceId,
            'model_type' => $pivot->getMorphClass(),
            'model_id' => $pivot->getKey(),
            'fields' => '',
            'changes' => $changes,
            'original' => $original,
            'status' => 'finished',
            'exception' => '',
        ]);
    }

    /**
     * Create new action event instances for resource deletes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public static function forResourceDelete($user, Collection $models): Collection
    {
        return static::forSoftDeleteAction('Delete', $user, $models);
    }

    /**
     * Create new action event instances for resource restorations.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public static function forResourceRestore($user, Collection $models): Collection
    {
        return static::forSoftDeleteAction('Restore', $user, $models);
    }

    /**
     * Create new action event instances for resource soft deletions.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public static function forSoftDeleteAction(string $action, $user, Collection $models): Collection
    {
        $batchId = (string) Str::orderedUuid();

        return $models->map(static fn ($model) => new static([
            'batch_id' => $batchId,
            'user_id' => $user->getAuthIdentifier(),
            'name' => $action,
            'actionable_type' => $model->getMorphClass(),
            'actionable_id' => $model->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'fields' => '',
            'original' => null,
            'changes' => null,
            'status' => 'finished',
            'exception' => '',
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ]));
    }

    /**
     * Create new action event instances for resource detachments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     */
    public static function forResourceDetach($user, $parent, Collection $models, string $pivotClass): Collection
    {
        $batchId = (string) Str::orderedUuid();

        return $models->map(static fn ($model) => new static([
            'batch_id' => $batchId,
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'Detach',
            'actionable_type' => $parent->getMorphClass(),
            'actionable_id' => $parent->getKey(),
            'target_type' => $model->getMorphClass(),
            'target_id' => $model->getKey(),
            'model_type' => $pivotClass,
            'model_id' => null,
            'fields' => '',
            'original' => null,
            'changes' => null,
            'status' => 'finished',
            'exception' => '',
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ]));
    }

    /**
     * Create the action records for the given models.
     */
    public static function createForModels(
        ActionRequest $request,
        Action $action,
        string $batchId,
        Collection $models,
        string $status = 'running'
    ): void {
        $models = $models->map(static fn ($model) => array_merge(
            static::defaultAttributes($request, $action, $batchId, $status),
            [
                'actionable_id' => $request->actionableKey($model),
                'target_id' => $request->targetKey($model),
                'model_id' => $model->getKey(),
            ]
        ));

        $models->chunk(50)->each(static function ($models) {
            static::insert($models->all());
        });

        static::prune($models);
    }

    /**
     * Get the default attributes for creating a new action event.
     *
     * @return array<string, mixed>
     */
    public static function defaultAttributes(
        ActionRequest $request,
        Action $action,
        string $batchId,
        string $status = 'running'
    ): array {
        if ($request->isPivotAction()) {
            $pivotClass = $request->pivotRelation()->getPivotClass();

            $modelType = collect(Relation::$morphMap)
                ->filter(static fn ($model) => $model === $pivotClass)
                ->keys()
                ->first() ?? $pivotClass;
        } else {
            $modelType = $request->actionableModel()->getMorphClass();
        }

        return [
            'batch_id' => $batchId,
            'user_id' => Nova::user($request)->getAuthIdentifier(),
            'name' => $action->name(),
            'actionable_type' => $request->actionableModel()->getMorphClass(),
            'target_type' => $request->model()->getMorphClass(),
            'model_type' => $modelType,
            'fields' => serialize($request->resolveFieldsForStorage()),
            'original' => null,
            'changes' => null,
            'status' => $status,
            'exception' => '',
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ];
    }

    /**
     * Prune the action events for the given types.
     */
    public static function prune(Collection $models, int $limit = 25): void
    {
        $models->each(static function ($model) use ($limit) {
            static::where('actionable_id', $model['actionable_id'])
                ->where('actionable_type', $model['actionable_type'])
                ->whereNotIn('id', static function ($query) use ($model, $limit) {
                    $query->select('id')->fromSub(
                        static::select('id')->orderBy('id', 'desc')
                                ->where('actionable_id', $model['actionable_id'])
                                ->where('actionable_type', $model['actionable_type'])
                                ->limit($limit)->toBase(),
                        'action_events_temp'
                    );
                })->delete();
        });
    }

    /**
     * Mark the given batch as running.
     */
    public static function markBatchAsRunning(string $batchId): int
    {
        return static::where('batch_id', $batchId)
                    ->whereNotIn('status', ['finished', 'failed'])->update([
                        'status' => 'running',
                    ]);
    }

    /**
     * Mark the given batch as finished.
     */
    public static function markBatchAsFinished(string $batchId): int
    {
        return static::where('batch_id', $batchId)
                    ->whereNotIn('status', ['finished', 'failed'])->update([
                        'status' => 'finished',
                    ]);
    }

    /**
     * Mark a given action event record as finished.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function markAsFinished(string $batchId, $model): int
    {
        return static::updateStatus($batchId, $model, 'finished');
    }

    /**
     * Mark the given batch as failed.
     *
     * @param  \Throwable  $e
     */
    public static function markBatchAsFailed(string $batchId, Throwable|string|null $e = null): int
    {
        return static::where('batch_id', $batchId)
                    ->whereNotIn('status', ['finished', 'failed'])->update([
                        'status' => 'failed',
                        'exception' => $e ? (string) $e : '',
                    ]);
    }

    /**
     * Mark a given action event record as failed.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function markAsFailed(string $batchId, $model, Throwable|string|null $e = null): int
    {
        return static::updateStatus($batchId, $model, 'failed', $e);
    }

    /**
     * Update the status of a given action event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function updateStatus(string $batchId, $model, string $status, Throwable|string|null $e = null): int
    {
        return static::where('batch_id', $batchId)
                        ->where('model_type', $model->getMorphClass())
                        ->where('model_id', $model->getKey())
                        ->update(['status' => $status, 'exception' => (string) $e]);
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return 'action_events';
    }
}
