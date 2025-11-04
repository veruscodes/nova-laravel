<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;

class Code extends Field
{
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'code-field';

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * Indicates if the field is used to manipulate JSON.
     *
     * @var bool
     */
    public $json = false;

    /**
     * The JSON encoding options.
     *
     * @var int|null
     */
    public $jsonOptions = null;

    /**
     * Indicates the visual height of the Code editor.
     *
     * @var string|int
     */
    public $height = 300;

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        $value = parent::resolveAttribute($resource, $attribute);

        if ($this->json) {
            return json_encode($value, $this->jsonOptions ?? JSON_PRETTY_PRINT);
        }

        return $value;
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
            $model->{$attribute} = $this->json
                ? json_decode($request[$requestAttribute], true)
                : $request[$requestAttribute];
        }
    }

    /**
     * Indicate that the code field is used to manipulate JSON.
     *
     * @return $this
     */
    public function json(?int $options = null)
    {
        $this->json = true;

        $this->jsonOptions = $options ?? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

        return $this->options(['mode' => 'application/json']);
    }

    /**
     * Define the language syntax highlighting mode for the field.
     *
     * @return $this
     */
    public function language(string $language)
    {
        return $this->options(['mode' => $language]);
    }

    /**
     * Set the Code editor to display all of its contents.
     *
     * @return $this
     */
    public function fullHeight()
    {
        $this->height = '100%';

        return $this;
    }

    /**
     * Set the visual height of the Code editor to automatic.
     *
     * @return $this
     */
    public function autoHeight()
    {
        $this->height = 'auto';

        return $this;
    }

    /**
     * Set the visual height of the Code editor.
     *
     * @return $this
     */
    public function height(string|int $height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set configuration options for the code editor instance.
     *
     * @return $this
     */
    public function options(array $options)
    {
        $currentOptions = $this->meta['options'] ?? [];

        return $this->withMeta([
            'options' => array_merge($currentOptions, $options),
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'height' => $this->height,
        ]);
    }
}
