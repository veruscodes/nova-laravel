<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\Nova;

class DashboardRequest extends NovaRequest
{
    /**
     * Get all of the possible cards for the request.
     */
    public function availableCards(string $dashboard): Collection
    {
        return Nova::availableDashboardCardsForDashboard($dashboard, $this);
    }
}
