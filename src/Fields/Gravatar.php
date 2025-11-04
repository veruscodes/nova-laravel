<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Nova;

/**
 * @method static static make(\Stringable|string|null $name = null, string $attribute = 'email')
 */
class Gravatar extends Avatar implements Unfillable
{
    /**
     * Create a new field.
     *
     * @param  \Stringable|string|null  $name
     */
    public function __construct($name = null, string $attribute = 'email')
    {
        parent::__construct($name ?? Nova::__('Avatar'), $attribute);

        $this->exceptOnForms()
            ->disableDownload();
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  \Laravel\Nova\Resource|\Illuminate\Database\Eloquent\Model|object  $resource
     */
    #[\Override]
    protected function resolveAttribute($resource, string $attribute): string
    {
        $callback = fn () => 'https://www.gravatar.com/avatar/'.md5(strtolower(parent::resolveAttribute($resource, $attribute))).'?s=300';

        $this->preview($callback)->thumbnail($callback);

        return \call_user_func($callback);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge([
            'indexName' => '',
        ], parent::jsonSerialize());
    }
}
