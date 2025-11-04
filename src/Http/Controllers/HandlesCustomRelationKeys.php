<?php

namespace Laravel\Nova\Http\Controllers;

use Laravel\Nova\Http\Requests\NovaRequest;

trait HandlesCustomRelationKeys
{
    /**
     * Determine if the user has set a custom relation key for the field.
     */
    protected function usingCustomRelationKey(NovaRequest $request): bool
    {
        return $request->relatedResource !== $request->viaRelationship;
    }

    /**
     * Get the rule key used for fetching the field's validation rules.
     */
    protected function getRuleKey(NovaRequest $request): string
    {
        return $this->usingCustomRelationKey($request)
            ? $request->viaRelationship
            : $request->relatedResource;
    }

    /**
     * Get the custom field attributes names for validation.
     */
    protected function customRulesKeys(NovaRequest $request, string $attribute): array
    {
        return [$this->getRuleKey($request) => $attribute];
    }
}
