<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\ResourceToolElement;

class NotExactlyAttached implements Rule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(public NovaRequest $request, public Model $model)
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function passes($attribute, $value)
    {
        /** @var \Illuminate\Database\Eloquent\Relations\MorphToMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany $relation */
        $relation = $this->model->{$this->request->viaRelationship}();

        $pivot = $relation->newPivot();
        $pivotAccessor = $relation->getPivotAccessor();
        $query = $relation->withoutGlobalScopes()
                        ->where($relation->getQualifiedRelatedPivotKeyName(), '=', $this->request->input($this->request->relatedResource));

        $resource = Nova::newResourceFromModel($this->model);

        $fields = $resource->resolvePivotFields($this->request, $this->request->relatedResource)
            ->reject(
                static fn ($field) => $field instanceof ResourceToolElement || $field->isComputed()
            );

        if ($fields->isEmpty()) {
            return true;
        }

        $fields->each(function ($field) use ($pivot) {
            $field->fill($this->request, $pivot);
        });

        $attributes = $pivot->toArray();

        foreach ($query->cursor() as $result) {
            $pivots = Arr::only($result->{$pivotAccessor}->toArray(), array_keys($attributes));

            if (array_diff_assoc(Arr::flatten($pivots), Arr::flatten($attributes)) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('nova::validation.attached');
    }
}
