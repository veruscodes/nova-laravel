<?php

namespace Laravel\Nova;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\DestructiveAction;
use Laravel\Nova\Contracts\ImpersonatesUsers;
use Laravel\Nova\Http\Requests\NovaRequest;

trait Authorizable
{
    /**
     * Determine if the given resource is authorizable.
     *
     * @return bool
     */
    public static function authorizable()
    {
        return ! \is_null(static::authorizationGate());
    }

    /**
     * Determine the given resource is authorizable gate.
     *
     * @return \Illuminate\Auth\Access\Gate|null
     */
    public static function authorizationGate()
    {
        return Gate::getPolicyFor(
            Util::resolveResourceOrModelForAuthorization(self::newResource())
        );
    }

    /**
     * Determine if the resource should be available for the given request.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToViewAny(Request $request)
    {
        if (! static::authorizable()) {
            return;
        }

        $gate = static::authorizationGate();

        if (\is_callable([$gate, 'viewAny'])) {
            $this->authorizeTo($request, 'viewAny');
        }
    }

    /**
     * Determine if the resource should be available for the given request.
     *
     * @return bool
     */
    public static function authorizedToViewAny(Request $request)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization(self::newResource());

        return \is_callable([$gate, 'viewAny'])
            ? Gate::forUser(Nova::user($request))->check('viewAny', $resource::class)
            : true;
    }

    /**
     * Determine if the current user can view the given resource or throw an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToView(Request $request)
    {
        $this->authorizeTo($request, 'view');
    }

    /**
     * Determine if the current user can view the given resource.
     *
     * @return bool
     */
    public function authorizedToView(Request $request)
    {
        return $this->authorizedTo($request, 'view');
    }

    /**
     * Determine if the current user can create new resources or throw an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorizeToCreate(Request $request)
    {
        throw_unless(static::authorizedToCreate($request), AuthorizationException::class);
    }

    /**
     * Determine if the current user can create new resources.
     *
     * @return bool
     */
    public static function authorizedToCreate(Request $request)
    {
        if (static::authorizable()) {
            $resource = Util::resolveResourceOrModelForAuthorization(self::newResource());

            return Gate::forUser(Nova::user($request))->check('create', $resource::class);
        }

        return true;
    }

    /**
     * Determine if the current user can update the given resource or throw an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToUpdate(Request $request)
    {
        $this->authorizeTo($request, 'update');
    }

    /**
     * Determine if the current user can update the given resource.
     *
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
        return $this->authorizedTo($request, 'update');
    }

    /**
     * Determine if the current user can replicate the given resource or throw an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToReplicate(Request $request)
    {
        if (! static::authorizable()) {
            return;
        }

        $gate = static::authorizationGate();

        if (\is_callable([$gate, 'replicate'])) {
            $this->authorizeTo($request, 'replicate');

            return;
        }

        $this->authorizeToCreate($request);
        $this->authorizeToUpdate($request);
    }

    /**
     * Determine if the current user can replicate the given resource.
     *
     * @return bool
     */
    public function authorizedToReplicate(Request $request)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);

        return \is_callable([$gate, 'replicate'])
            ? Gate::forUser(Nova::user($request))->check('replicate', $resource)
            : $this->authorizedToCreate($request) && $this->authorizedToUpdate($request);
    }

    /**
     * Determine if the current user can delete the given resource or throw an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToDelete(Request $request)
    {
        $this->authorizeTo($request, 'delete');
    }

    /**
     * Determine if the current user can delete the given resource.
     *
     * @return bool
     */
    public function authorizedToDelete(Request $request)
    {
        return $this->authorizedTo($request, 'delete');
    }

    /**
     * Determine if the current user can restore the given resource.
     *
     * @return bool
     */
    public function authorizedToRestore(Request $request)
    {
        return $this->authorizedTo($request, 'restore');
    }

    /**
     * Determine if the current user can force delete the given resource.
     *
     * @return bool
     */
    public function authorizedToForceDelete(Request $request)
    {
        return $this->authorizedTo($request, 'forceDelete');
    }

    /**
     * Determine if the user can add / associate models of the given type to the resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return bool
     */
    public function authorizedToAdd(NovaRequest $request, $model)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'add'.class_basename($model);

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource, $model])
            : true;
    }

    /**
     * Determine if the user can attach any models of the given type to the resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return bool
     */
    public function authorizedToAttachAny(NovaRequest $request, $model)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'attachAny'.Str::singular(class_basename($model));

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource])
            : true;
    }

    /**
     * Determine if the user can attach models of the given type to the resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return bool
     */
    public function authorizedToAttach(NovaRequest $request, $model)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'attach'.Str::singular(class_basename($model));

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource, $model])
            : true;
    }

    /**
     * Determine if the user can detach models of the given type to the resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string  $relationship
     * @return bool
     */
    public function authorizedToDetach(NovaRequest $request, $model, $relationship)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'detach'.Str::singular(class_basename($model));

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource, $model])
            : true;
    }

    /**
     * Determine if the user can run the given action.
     *
     * @return bool
     */
    public function authorizedToRunAction(NovaRequest $request, Action $action)
    {
        if ($action instanceof DestructiveAction) {
            return $this->authorizedToRunDestructiveAction($request, $action);
        }

        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'runAction';

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource, $action])
            : $this->authorizedToUpdate($request);
    }

    /**
     * Determine if the user can run the given action.
     *
     * @return bool
     */
    public function authorizedToRunDestructiveAction(NovaRequest $request, DestructiveAction $action)
    {
        if (! static::authorizable()) {
            return true;
        }

        $gate = static::authorizationGate();

        $resource = Util::resolveResourceOrModelForAuthorization($this);
        $method = 'runDestructiveAction';

        return \is_callable([$gate, $method])
            ? Gate::forUser(Nova::user($request))->check($method, [$resource, $action])
            : $this->authorizedToDelete($request);
    }

    /**
     * Determine if the current user can impersonate the given resource.
     *
     * @return bool
     */
    public function authorizedToImpersonate(NovaRequest $request)
    {
        $user = Nova::user($request);

        $resource = $this->model();

        return app(ImpersonatesUsers::class)->impersonating($request) === false
            && ! $resource->is($user)
            && $resource instanceof Authenticatable
            && (method_exists($resource, 'canBeImpersonated') && $resource->canBeImpersonated() === true)
            && (method_exists($user, 'canImpersonate') && $user->canImpersonate() === true);
    }

    /**
     * Determine if the current user has a given ability.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeTo(Request $request, string $ability): void
    {
        if (static::authorizable()) {
            $resource = Util::resolveResourceOrModelForAuthorization($this);

            Gate::forUser(Nova::user($request))->authorize($ability, $resource);
        }
    }

    /**
     * Determine if the current user can view the given resource.
     */
    public function authorizedTo(Request $request, string $ability): bool
    {
        $resource = Util::resolveResourceOrModelForAuthorization($this);

        return static::authorizable()
            ? Gate::forUser(Nova::user($request))->check($ability, $resource)
            : true;
    }
}
