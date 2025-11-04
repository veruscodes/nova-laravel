<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

class NotAttached implements Rule
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
        return ! \in_array(
            $this->request->input($this->request->relatedResource),
            $this->model->{$this->request->viaRelationship}()
                ->withoutGlobalScopes()->get()->modelKeys()
        );
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
