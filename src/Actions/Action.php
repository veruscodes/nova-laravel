<?php

namespace Laravel\Nova\Actions;

use Closure;
use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Exceptions\MissingActionHandlerException;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Metable;
use Laravel\Nova\Nova;
use Laravel\Nova\ProxiesCanSeeToGate;
use Laravel\Nova\URL;
use Laravel\Nova\WithComponent;
use ReflectionClass;
use Stringable;

/**
 * @phpstan-type TModel \Illuminate\Database\Eloquent\Model
 * @phpstan-type TAuthoriseCallback \Closure(\Laravel\Nova\Http\Requests\NovaRequest):bool
 *
 * @phpstan-property TAuthoriseCallback|null $seeCallback
 *
 * @phpstan-method $this canSee(TAuthoriseCallback $callback)
 *
 * @property \Closure|null $seeCallback
 *
 * @method $this canSee(\Closure $callback)
 */
#[\AllowDynamicProperties]
class Action implements JsonSerializable
{
    use AuthorizedToSee;
    use Macroable;
    use Makeable;
    use Metable;
    use ProxiesCanSeeToGate;
    use Tappable;
    use WithComponent;

    public const FULLSCREEN_STYLE = 'fullscreen';

    public const WINDOW_STYLE = 'window';

    /**
     * The number of models that should be included in each chunk.
     *
     * @var int
     */
    public static $chunkCount = 200;

    /**
     * The displayable name of the action.
     *
     * @var \Stringable|string
     */
    public $name;

    /**
     * The URI key of the action.
     *
     * @var string|null
     */
    public $uriKey;

    /**
     * The action's component.
     *
     * @var string
     */
    public $component = 'confirm-action-modal';

    /**
     * Indicates if need to skip log action events for models.
     *
     * @var bool
     */
    public $withoutActionEvents = false;

    /**
     * Determine where the action redirection should be without confirmation.
     *
     * @var bool
     */
    public $withoutConfirmation = false;

    /**
     * Indicates if this action is only available on the resource index view.
     *
     * @var bool
     */
    public $onlyOnIndex = false;

    /**
     * Indicates if this action is only available on the resource detail view.
     *
     * @var bool
     */
    public $onlyOnDetail = false;

    /**
     * Indicates if this action is available on the resource index view.
     *
     * @var bool
     */
    public $showOnIndex = true;

    /**
     * Indicates if this action is available on the resource detail view.
     *
     * @var bool
     */
    public $showOnDetail = true;

    /**
     * Indicates if this action is available on the resource's table row.
     *
     * @var bool
     */
    public $showInline = false;

    /**
     * The current batch ID being handled by the action.
     *
     * @var string|null
     */
    public $actionBatchId = null;

    /**
     * The callback used to authorize running the action.
     *
     * @var (\Closure(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|null
     */
    public $runCallback;

    /**
     * The callback that should be invoked when the action has completed.
     *
     * @var (callable(\Illuminate\Support\Collection):(mixed))|null
     */
    public $thenCallback;

    /**
     * The text to be used for the action's confirm button.
     *
     * @var \Stringable|string
     */
    public $confirmButtonText = 'Run Action';

    /**
     * The text to be used for the action's cancel button.
     *
     * @var \Stringable|string
     */
    public $cancelButtonText = 'Cancel';

    /**
     * The text to be used for the action's confirmation text.
     *
     * @var \Stringable|string
     */
    public $confirmText = 'Are you sure you want to run this action?';

    /**
     * Indicates if the action can be run without any models.
     *
     * @var bool
     */
    public $standalone = false;

    /**
     * Indicates if the action can be run with a single selected model.
     *
     * @var bool
     */
    public $sole = false;

    /**
     * The XHR response type on executing the action.
     *
     * @var string
     */
    public $responseType = 'json';

    /**
     * The size of the modal. Can be "sm", "md", "lg", "xl", "2xl", "3xl", "4xl", "5xl", "6xl", "7xl".
     *
     * @var string
     */
    public $modalSize = '2xl';

    /**
     * The style of the modal. Can be either 'fullscreen' or 'window'.
     *
     * @var string
     */
    public $modalStyle = 'window';

    /**
     * Indicates if the action is authorized to run.
     *
     * @var bool|null
     */
    public $authorizedToRunAction = null;

    /**
     * The closure used to handle the action.
     *
     * @var (\Closure(\Laravel\Nova\Fields\ActionFields, \Illuminate\Support\Collection):(mixed))|null
     */
    public $handleCallback = null;

    /**
     * The underlying model resource instance.
     *
     * @var TModel|null
     */
    public $resource;

    /**
     * Create a new action using the given callback.
     *
     * @param  \Stringable|string  $name
     * @param  \Closure(\Laravel\Nova\Fields\ActionFields, \Illuminate\Support\Collection):(mixed)  $handleUsing
     */
    public static function using($name, Closure $handleUsing): static
    {
        return (new static)
            ->withName($name)
            ->handleUsing($handleUsing);
    }

    /**
     * Set the Closure used to handle the action.
     *
     * @param  \Closure(\Laravel\Nova\Fields\ActionFields, \Illuminate\Support\Collection):(mixed)  $callback
     * @return $this
     */
    public function handleUsing(Closure $callback)
    {
        $this->handleCallback = $callback;

        return $this;
    }

    /**
     * Set the name for the action.
     *
     * @return $this
     */
    public function withName(Stringable|string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return a message response from the action.
     */
    public static function message(Stringable|string $message): ActionResponse
    {
        return ActionResponse::message($message);
    }

    /**
     * Return a delete response from the action.
     */
    public static function deleted(): ActionResponse
    {
        return ActionResponse::deleted();
    }

    /**
     * Return a redirect response from the action.
     *
     * @no-named-arguments
     *
     * @param  (\Closure():(string))|(\Closure(\Illuminate\Database\Eloquent\Model):(string))|string|null  $url
     */
    public static function redirect(Stringable|string $name, Closure|string|null $url = null): static|ActionResponse
    {
        if (\func_num_args() === 2) {
            return (new static)
                ->withName($name)
                ->noop()
                ->tap(static function ($action) use ($url) {
                    $action->handleUsing(static function ($fields, $models) use ($action, $url) {
                        if ($action->sole === true) {
                            return ActionResponse::redirect(value($url, $models->first()));
                        }

                        return ActionResponse::redirect(value($url));
                    });
                })
                ->then(static fn ($response) => $response->first());
        }

        return ActionResponse::redirect($name);
    }

    /**
     * Register a callback that should be invoked after the action is finished executing.
     *
     * @param  callable(\Illuminate\Support\Collection):mixed  $callback
     * @return $this
     */
    public function then(callable $callback)
    {
        $this->thenCallback = $callback;

        return $this;
    }

    /**
     * Set the Action to be a no-op.
     *
     * @return $this
     */
    public function noop()
    {
        return $this->handleUsing(fn () => null);
    }

    /**
     * Return a Inertia visit from the action.
     *
     * @param  (\Closure():(string))|(\Closure(\Illuminate\Database\Eloquent\Model):(string))|string|array<string, mixed>  $path
     * @param  array<string, mixed>  $options
     *
     * @deprecated 4.0.0 Use "Action::visit()" instead
     */
    #[\Deprecated('Use `visit()` method instead', since: '4.0.0')]
    public static function push(Stringable|URL|string $name, Closure|URL|string|array $path, array $options = []): static|ActionResponse
    {
        return self::visit($name, $path, $options);
    }

    /**
     * Return a Inertia visit from the action.
     *
     * @no-named-arguments
     *
     * @template TVisit of \Laravel\Nova\URL|string
     * @template TQueryString of array<string, mixed>
     *
     * @param  TVisit|\Stringable  $name
     * @param  (\Closure():(TVisit))|(\Closure(\Illuminate\Database\Eloquent\Model):(TVisit))|TVisit|TQueryString  $path
     * @param  TQueryString  $options
     */
    public static function visit(Stringable|URL|string $name, Closure|URL|string|array $path = [], array $options = []): static|ActionResponse
    {
        if (\func_num_args() <= 2 && \is_array($path)) {
            return ActionResponse::visit($name, $path);
        }

        return (new static)
            ->withName($name)
            ->noop()
            ->tap(function ($action) use ($path, $options) {
                $action->handleUsing(function ($fields, $models) use ($action, $path, $options) {
                    if ($action->sole === true) {
                        return ActionResponse::visit(value($path, $models->first()), $options);
                    }

                    return ActionResponse::visit(value($path), $options);
                });
            })
            ->then(static fn ($response) => $response->first());
    }

    /**
     * Return an open in new tab response from the action.
     *
     * @no-named-arguments
     *
     * @param  (\Closure():(string))|(\Closure(\Illuminate\Database\Eloquent\Model):(string))|string|null  $url
     */
    public static function openInNewTab(Stringable|string $name, Closure|string|null $url = null): static|ActionResponse
    {
        if (\func_num_args() === 2) {
            return (new static)
                ->withName($name)
                ->noop()
                ->tap(function ($action) use ($url) {
                    $action->handleUsing(function ($fields, $models) use ($action, $url) {
                        if ($action->sole === true) {
                            return ActionResponse::openInNewTab(value($url, $models->first()));
                        }

                        return ActionResponse::openInNewTab(value($url));
                    });
                })
                ->then(static fn ($response) => $response->first());
        }

        return ActionResponse::openInNewTab($name);
    }

    /**
     * Return a download response from the action.
     *
     * @param  (\Closure():(string))|(\Closure(\Illuminate\Database\Eloquent\Model):(string))|string  $url
     */
    public static function downloadURL(Stringable|string $name, Closure|string $url): static
    {
        return (new static)
            ->withName($name)
            ->noop()
            ->tap(function ($action) use ($name, $url) {
                $action->handleUsing(function ($fields, $models) use ($action, $name, $url) {
                    if ($action->sole === true) {
                        return ActionResponse::download($name, value($url, $models->first()));
                    }

                    return ActionResponse::download($name, value($url));
                });
            })
            ->then(static fn ($response) => $response->first());
    }

    /**
     * Return a download response from the action.
     *
     * @deprecated 4.31.2 Use "Action::downloadURL()" instead
     */
    #[\Deprecated('Use `downloadURL()` method instead', since: '4.31.2')]
    public static function download(string $url, Stringable|string $name): ActionResponse
    {
        return ActionResponse::download($name, $url);
    }

    /**
     * Return an action modal response from the action.
     *
     * @no-named-arguments
     *
     * @param  string|array<string, mixed>  $modal
     * @param  (\Closure():(array<string, mixed>))|(\Closure(\Illuminate\Database\Eloquent\Model):(array<string, mixed>))|array<string, mixed>  $data
     */
    public static function modal(Stringable|string $name, string|array $modal = [], Closure|array $data = []): static|ActionResponse
    {
        if (\func_num_args() === 3) {
            return (new static)
                ->withName($name)
                ->noop()
                ->tap(function ($action) use ($modal, $data) {
                    $action->handleUsing(function ($fields, $models) use ($action, $modal, $data) {
                        if ($action->sole === true) {
                            return ActionResponse::modal($modal, value($data, $models->first()));
                        }

                        return ActionResponse::modal($modal, value($data));
                    });
                })
                ->then(static fn ($response) => $response->first());
        }

        return ActionResponse::modal($name, $modal);
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function authorizedToRun(Request $request, $model)
    {
        return $this->authorizedToRunAction = value($this->runCallback ?? true, $request, $model);
    }

    /**
     * Set the Action to be available only when single resource is selected.
     *
     * @return $this
     */
    public function sole()
    {
        $this->standalone = false;
        $this->sole = true;

        return $this->showInline()->showOnDetail();
    }

    /**
     * Show the action on the detail view.
     *
     * @return $this
     */
    public function showOnDetail()
    {
        $this->showOnDetail = true;

        return $this;
    }

    /**
     * Show the action on the table row.
     *
     * @return $this
     */
    public function showInline()
    {
        $this->showInline = true;

        return $this;
    }

    /**
     * Perform the action on the given models using the provided handle callback.
     *
     * @return mixed
     */
    public function handleUsingCallback(ActionFields $fields, Collection $models)
    {
        return value($this->handleCallback, $fields, $models);
    }

    /**
     * Execute the action for the given request.
     *
     * @return mixed
     *
     * @throws \Laravel\Nova\Exceptions\MissingActionHandlerException|\Throwable
     */
    public function handleRequest(ActionRequest $request)
    {
        $fields = $request->resolveFields();

        $dispatcher = new DispatchAction($request, $this, $fields);

        if (method_exists($this, 'dispatchRequestUsing')) {
            $dispatcher->handleUsing(
                $request,
                fn ($request, $response, $fields) => $this->dispatchRequestUsing($request, $response, $fields)
            );
        } else {
            $method = ActionMethod::determine($this, $request->targetModel());

            if (! method_exists($this, $method)) {
                throw MissingActionHandlerException::make($this, $method);
            }

            $this->standalone
                ? $dispatcher->handleStandalone($method)
                : $dispatcher->handleRequest($request, $method, static::$chunkCount);
        }

        $response = $dispatcher->dispatch();

        if (! $response->wasExecuted) {
            return static::danger(Nova::__('Sorry! You are not authorized to perform this action.'));
        }

        if ($this->thenCallback) {
            return \call_user_func($this->thenCallback, collect($response->results)->flatten());
        }

        return $this->handleResult($fields, $response->results);
    }

    /**
     * Return a dangerous message response from the action.
     *
     * @no-named-arguments
     */
    public static function danger(Stringable|string $name, ?string $message = null): static|ActionResponse
    {
        if (\func_num_args() === 2) {
            return (new static)
                ->withName($name)
                ->noop()
                ->then(static fn () => ActionResponse::danger($message));
        }

        return ActionResponse::danger($name);
    }

    /**
     * Handle chunk results.
     *
     * @param  array<int, mixed>  $results
     * @return mixed
     */
    public function handleResult(ActionFields $fields, array $results)
    {
        return \count($results) ? end($results) : null;
    }

    /**
     * Validate the given request.
     *
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateFields(ActionRequest $request): array
    {
        /** @var \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field> $fields */
        $fields = FieldCollection::make($this->fields($request))
            ->authorized($request)
            ->applyDependsOn($request)
            ->withoutReadonly($request)
            ->withoutUnfillable();

        return Validator::make(
            $request->all(),
            $fields->mapWithKeys(static fn ($field) => $field->getCreationRules($request))->all(),
            [],
            $fields->reject(static fn ($field) => empty($field->name))
                ->mapWithKeys(static fn ($field) => [$field->attribute => $field->name])
                ->all()
        )->after(function ($validator) use ($request) {
            $this->afterValidation($request, $validator);
        })->validate();
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }

    /**
     * Handle any post-validation processing.
     *
     * @return void
     */
    protected function afterValidation(NovaRequest $request, ValidatorContract $validator)
    {
        //
    }

    /**
     * Indicate that this action is only available on the resource index view.
     *
     * @param  bool  $value
     * @return $this
     */
    public function onlyOnIndex($value = true)
    {
        $this->onlyOnIndex = $value;
        $this->showOnIndex = $value;
        $this->showOnDetail = ! $value;
        $this->showInline = ! $value;

        return $this;
    }

    /**
     * Indicate that this action is available except on the resource index view.
     *
     * @return $this
     */
    public function exceptOnIndex()
    {
        $this->showOnDetail = true;
        $this->showInline = true;
        $this->showOnIndex = false;

        return $this;
    }

    /**
     * Indicate that this action is only available on the resource detail view.
     *
     * @param  bool  $value
     * @return $this
     */
    public function onlyOnDetail($value = true)
    {
        $this->onlyOnDetail = $value;
        $this->showOnDetail = $value;
        $this->showOnIndex = ! $value;
        $this->showInline = ! $value;

        return $this;
    }

    /**
     * Indicate that this action is available except on the resource detail view.
     *
     * @return $this
     */
    public function exceptOnDetail()
    {
        $this->showOnIndex = true;
        $this->showOnDetail = false;
        $this->showInline = true;

        return $this;
    }

    /**
     * Indicate that this action is only available on the resource's table row.
     *
     * @param  bool  $value
     * @return $this
     */
    public function onlyOnTableRow($value = true)
    {
        return $this->onlyInline($value);
    }

    /**
     * Indicate that this action is only available on the resource's table row.
     *
     * @param  bool  $value
     * @return $this
     */
    public function onlyInline($value = true)
    {
        $this->showInline = $value;
        $this->showOnIndex = ! $value;
        $this->showOnDetail = ! $value;

        return $this;
    }

    /**
     * Indicate that this action is available except on the resource's table row.
     *
     * @return $this
     */
    public function exceptOnTableRow()
    {
        return $this->exceptInline();
    }

    /**
     * Indicate that this action is available except on the resource's table row.
     *
     * @return $this
     */
    public function exceptInline()
    {
        $this->showInline = false;
        $this->showOnIndex = true;
        $this->showOnDetail = true;

        return $this;
    }

    /**
     * Show the action on the index view.
     *
     * @return $this
     */
    public function showOnIndex()
    {
        $this->showOnIndex = true;

        return $this;
    }

    /**
     * Show the action on the table row.
     *
     * @return $this
     *
     * @deprecated 4.0.0 Use "Action::showInline()" instead
     */
    #[\Deprecated('Use `showInline()` method instead', since: '4.0.0')]
    public function showOnTableRow()
    {
        return $this->showInline();
    }

    /**
     * Set the current batch ID being handled by the action.
     *
     * @return $this
     */
    public function withActionBatchId(string $actionBatchId)
    {
        $this->actionBatchId = $actionBatchId;

        return $this;
    }

    /**
     * Register `then`, `catch`, and `finally` callbacks on the pending batch.
     *
     * @return void
     */
    public function withBatch(ActionFields $fields, PendingBatch $batch)
    {
        //
    }

    /**
     * Set the callback to be run to authorize running the action.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\NovaRequest, mixed):bool  $callback
     * @return $this
     */
    public function canRun(Closure $callback)
    {
        $this->runCallback = $callback;

        return $this;
    }

    /**
     * Set the URI key for the action.
     *
     * @return $this
     */
    public function withUriKey(string $uriKey)
    {
        $this->uriKey = $uriKey;

        return $this;
    }

    /**
     * Set the action to execute instantly.
     *
     * @return $this
     */
    public function withoutConfirmation()
    {
        $this->withoutConfirmation = true;

        return $this;
    }

    /**
     * Set the action to skip action events for models.
     *
     * @return $this
     */
    public function withoutActionEvents()
    {
        $this->withoutActionEvents = true;

        return $this;
    }

    /**
     * Set the text for the action's confirmation button.
     *
     * @return $this
     */
    public function confirmButtonText(Stringable|string $text)
    {
        $this->confirmButtonText = $text;

        return $this;
    }

    /**
     * Set the text for the action's cancel button.
     *
     * @return $this
     */
    public function cancelButtonText(Stringable|string $text)
    {
        $this->cancelButtonText = $text;

        return $this;
    }

    /**
     * Set the text for the action's confirmation message.
     *
     * @return $this
     */
    public function confirmText(Stringable|string $text)
    {
        $this->confirmText = $text;

        return $this;
    }

    /**
     * Mark the action as a standalone action.
     *
     * @return $this
     */
    public function standalone()
    {
        $this->standalone = true;
        $this->sole = false;

        return $this;
    }

    /**
     * Set the modal to fullscreen style.
     *
     * @return $this
     */
    public function fullscreen()
    {
        $this->modalStyle = static::FULLSCREEN_STYLE;

        return $this;
    }

    /**
     * Set the size of the modal window.
     *
     * @return $this
     */
    public function size(string $size)
    {
        $this->modalStyle = static::WINDOW_STYLE;
        $this->modalSize = $size;

        return $this;
    }

    /**
     * Prepare the action for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $request = app(NovaRequest::class);

        return array_merge([
            'cancelButtonText' => Nova::__($this->cancelButtonText),
            'component' => $this->component(),
            'confirmButtonText' => Nova::__($this->confirmButtonText),
            'confirmText' => Nova::__($this->confirmText),
            'destructive' => $this instanceof DestructiveAction,
            'authorizedToRun' => $this->authorizedToRunAction,
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
            'fields' => FieldCollection::make($this->fields($request))
                ->filter->authorizedToSee($request)
                ->each->resolveForAction($request)
                ->applyDependsOnWithDefaultValues($request)
                ->values()
                ->all(),
            'showOnDetail' => $this->shownOnDetail(),
            'showOnIndex' => $this->shownOnIndex(),
            'showOnTableRow' => $this->shownOnTableRow(),
            'standalone' => $this->isStandalone(),
            'modalSize' => $this->modalSize,
            'modalStyle' => $this->modalStyle,
            'responseType' => $this->responseType,
            'withoutConfirmation' => $this->withoutConfirmation,
        ], $this->meta());
    }

    /**
     * Get the displayable name of the action.
     *
     * @return \Stringable|string
     */
    public function name()
    {
        return $this->name ?: Nova::humanize($this);
    }

    /**
     * Get the URI key for the action.
     *
     * @return string
     */
    public function uriKey()
    {
        return $this->uriKey ?? Str::slug($this->name(), '-', null);
    }

    /**
     * Determine if the action is to be shown on the detail view.
     */
    public function shownOnDetail(): bool
    {
        if ($this->onlyOnDetail) {
            return true;
        }

        if ($this->onlyOnIndex) {
            return false;
        }

        return $this->showOnDetail;
    }

    /**
     * Determine if the action is to be shown on the index view.
     */
    public function shownOnIndex(): bool
    {
        if ($this->onlyOnIndex == true) {
            return true;
        }

        if ($this->onlyOnDetail) {
            return false;
        }

        return $this->showOnIndex;
    }

    /**
     * Determine if the action is to be shown inline on the table row.
     */
    public function shownOnTableRow(): bool
    {
        return $this->showInline;
    }

    /**
     * Determine if the action is a standalone action.
     */
    public function isStandalone(): bool
    {
        return $this->standalone;
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        return array_values(array_filter(array_map(static function ($property) {
            return ($property->isStatic() || \in_array($name = $property->getName(),
                ['runCallback', 'seeCallback', 'thenCallback'])) ? null : $name;
        }, $properties)));
    }

    /**
     * Mark the action event record for the model as finished.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return int
     */
    protected function markAsFinished($model)
    {
        return $this->actionBatchId ? Nova::usingActionEvent(function ($actionEvent) use ($model) {
            $actionEvent->markAsFinished($this->actionBatchId, $model);
        }) : 0;
    }

    /**
     * Mark the action event record for the model as failed.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Throwable|string  $e
     * @return int
     */
    protected function markAsFailed($model, $e = null)
    {
        return $this->actionBatchId ? Nova::usingActionEvent(function ($actionEvent) use ($model, $e) {
            $actionEvent->markAsFailed($this->actionBatchId, $model, $e);
        }) : 0;
    }
}
