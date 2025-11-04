<?php

namespace Laravel\Nova\Fields;

use Laravel\Nova\Contracts\Cover;
use Laravel\Nova\Nova;

/**
 * @method static static make(\Stringable|string|null $name = null, string|callable|null $attribute = null, string|null $disk = null, callable|null $storageCallback = null)
 */
class Avatar extends Image implements Cover
{
    /**
     * Create a new field.
     *
     * @param  \Stringable|string|null  $name
     * @param  string|callable|null  $attribute
     * @param  (callable(\Illuminate\Http\Request, object, string, string, ?string, ?string):(mixed))|null  $storageCallback
     */
    public function __construct($name = null, mixed $attribute = null, ?string $disk = null, ?callable $storageCallback = null)
    {
        if (\is_null($name)) {
            $attribute ??= 'avatar';
            $name = Nova::__('Avatar');
        }

        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->rounded();
    }

    /**
     * Create Avatar field using Gravatar service.
     *
     * @param  \Stringable|string|null  $name
     */
    public static function gravatar($name = null, string $attribute = 'email'): Gravatar
    {
        return new Gravatar($name, $attribute);
    }

    /**
     * Create Avatar field using ui-avatars service.
     *
     * @param  \Stringable|string|null  $name
     */
    public static function uiavatar($name = null, string $attribute = 'name'): UiAvatar
    {
        return new UiAvatar($name, $attribute);
    }
}
