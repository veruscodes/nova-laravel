<?php

namespace Laravel\Nova;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

trait HasLifecycleMethods
{
    /**
     * Register a callback to be called before the resource is created.
     *
     * @return void
     */
    public static function beforeCreate(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called after the resource is created.
     *
     * @return void
     */
    public static function afterCreate(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called before the resource is updated.
     *
     * @return void
     */
    public static function beforeUpdate(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called after the resource is updated.
     *
     * @return void
     */
    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called before the resource is deleted.
     *
     * @return void
     */
    public static function beforeDelete(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called after the resource is deleted.
     *
     * @return void
     */
    public static function afterDelete(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called before the resource is force-deleted.
     *
     * @return void
     */
    public static function beforeForceDelete(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called after the resource is force-deleted.
     *
     * @return void
     */
    public static function afterForceDelete(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called before the resource is restored.
     *
     * @return void
     */
    public static function beforeRestore(NovaRequest $request, Model $model)
    {
        //
    }

    /**
     * Register a callback to be called after the resource is restored.
     *
     * @return void
     */
    public static function afterRestore(NovaRequest $request, Model $model)
    {
        //
    }
}
