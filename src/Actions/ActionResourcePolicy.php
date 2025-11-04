<?php

namespace Laravel\Nova\Actions;

class ActionResourcePolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function view($user, ActionResource $actionResource): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function create($user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can replicate the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function replicate($user, ActionResource $actionResource): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, ActionResource $actionResource): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, ActionResource $actionResource): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function restore($user, ActionResource $actionResource): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function forceDelete($user, ActionResource $actionResource): bool
    {
        return false;
    }
}
