<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Stringable;

class KeyValue extends Field
{
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'key-value-field';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * The label that should be used for the key heading.
     *
     * @var \Stringable|string|null
     */
    public $keyLabel = null;

    /**
     * The label that should be used for the value heading.
     *
     * @var \Stringable|string|null
     */
    public $valueLabel = null;

    /**
     * The label that should be used for the "add row" button.
     *
     * @var \Stringable|string|null
     */
    public $actionText = null;

    /**
     * The callback used to determine if the keys are readonly.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $readonlyKeysCallback;

    /**
     * Determine if new rows are able to be added.
     *
     * @var bool
     */
    public $canAddRow = true;

    /**
     * Determine if rows are able to be deleted.
     *
     * @var bool
     */
    public $canDeleteRow = true;

    /**
     * Resolve the field's value.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        if ($this->value === '{}') {
            $this->value = null;
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
    {
        if ($request->exists($requestAttribute)) {
            // The value for KeyValue fields are serialized on the front-end using `JSON.stringify`,
            // so we need to convert it to an associative array before saving it to the database.
            $this->fillModelWithData($model, json_decode($request[$requestAttribute], true), $attribute);
        }
    }

    /**
     * Fill the model's attribute with data.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Laravel\Nova\Support\Fluent  $model
     */
    #[\Override]
    public function fillModelWithData(object $model, mixed $value, string $attribute): void
    {
        $model->forceFill([str_replace('.', '->', $attribute) => $value]);
    }

    /**
     * The label that should be used for the key table heading.
     *
     * @return $this
     */
    public function keyLabel(Stringable|string $label)
    {
        $this->keyLabel = $label;

        return $this;
    }

    /**
     * The label that should be used for the value table heading.
     *
     * @return $this
     */
    public function valueLabel(Stringable|string $label)
    {
        $this->valueLabel = $label;

        return $this;
    }

    /**
     * The label that should be used for the add row button.
     *
     * @return $this
     */
    public function actionText(Stringable|string $label)
    {
        $this->actionText = $label;

        return $this;
    }

    /**
     * Set the callback used to determine if the keys are readonly.
     *
     * @param  (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool  $callback
     * @return $this
     */
    public function disableEditingKeys(callable|bool $callback = true)
    {
        $this->readonlyKeysCallback = $callback;

        return $this;
    }

    /**
     * Determine if the keys are readonly.
     */
    public function readonlyKeys(NovaRequest $request): bool
    {
        return with($this->readonlyKeysCallback, static function ($callback) use ($request) {
            return \is_callable($callback) ? \call_user_func($callback, $request) : ($callback === true);
        });
    }

    /**
     * Disable adding new rows.
     *
     * @return $this
     */
    public function disableAddingRows()
    {
        $this->canAddRow = false;

        return $this;
    }

    /**
     * Disable deleting rows.
     *
     * @return $this
     */
    public function disableDeletingRows()
    {
        $this->canDeleteRow = false;

        return $this;
    }

    /**
     * Prepare the field element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'keyLabel' => $this->keyLabel ?? Nova::__('Key'),
            'valueLabel' => $this->valueLabel ?? Nova::__('Value'),
            'actionText' => $this->actionText ?? Nova::__('Add row'),
            'readonlyKeys' => $this->readonlyKeys(app(NovaRequest::class)),
            'canAddRow' => $this->canAddRow,
            'canDeleteRow' => $this->canDeleteRow,
        ]);
    }
}
