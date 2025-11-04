<?php

namespace Laravel\Nova\Http\Resources;

use Laravel\Nova\Dashboard;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Http\Requests\DashboardRequest;
use Laravel\Nova\Nova;

class DashboardViewResource extends Resource
{
    /**
     * Construct a new Dashboard Resource.
     */
    public function __construct(protected string $name)
    {
        //
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Laravel\Nova\Http\Requests\DashboardRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        $dashboard = $this->authorizedDashboardForRequest($request);

        return [
            'label' => $dashboard->label(),
            'cards' => $request->availableCards($this->name),
            'showRefreshButton' => $dashboard->showRefreshButton,
            'isHelpCard' => $dashboard instanceof Main,
        ];
    }

    /**
     * Get authorized dashboard for the request.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizedDashboardForRequest(DashboardRequest $request): Dashboard
    {
        return tap(Nova::dashboardForKey($this->name, $request), static function ($dashboard) {
            abort_if(\is_null($dashboard), 404);
        });
    }
}
