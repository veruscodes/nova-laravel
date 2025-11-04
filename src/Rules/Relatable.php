<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class Relatable implements Rule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        public NovaRequest $request,
        public Builder $query,
        public Field&RelatableField $field
    ) {
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
        $model = $this->query->tap(static function ($query) {
            tap($query->getQuery(), static function ($builder) {
                /** @var \Illuminate\Database\Query\Builder $builder */
                $builder->reorder();

                $builder->select(
                    ! empty($builder->joins) ? $builder->from.'.*' : '*'
                );
            });
        })->whereKey($value)->first();

        if (! $model) {
            return false;
        }

        if ($this->relationshipIsFull($model, $attribute, $value)) {
            return false;
        }

        if ($resourceClass = ($this->field->resourceClass ?? Nova::resourceForModel($model))) {
            return $this->authorize($resourceClass, $model);
        }

        return true;
    }

    /**
     * Determine if the relationship is "full".
     */
    protected function relationshipIsFull(Model $model, string $attribute, mixed $value): bool
    {
        $inverseRelation = $this->request->newResource()
                    ->resolveInverseFieldsForAttribute($this->request, $attribute)->first(static function ($field) {
                        return ($field instanceof MorphOne || $field instanceof HasOne) && ! $field->ofManyRelationship();
                    });

        if ($inverseRelation && $this->request->resourceId) {
            $modelBeingUpdated = $this->request->findModelOrFail();

            if (\is_null($modelBeingUpdated->{$attribute})) {
                return false;
            }

            if ($modelBeingUpdated->{$attribute}->getKey() == $value) {
                return false;
            }
        }

        return $inverseRelation &&
               $model->{$inverseRelation->attribute}()->count() > 0;
    }

    /**
     * Authorize that the user is allowed to relate this resource.
     *
     * @param  class-string<\Laravel\Nova\Resource>  $resourceClass
     */
    protected function authorize(string $resourceClass, Model $model): bool
    {
        return $resourceClass::make($model)
            ->authorizedToAdd($this->request, $this->request->model());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('nova::validation.relatable');
    }
}
