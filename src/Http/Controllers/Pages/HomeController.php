<?php

namespace Laravel\Nova\Http\Controllers\Pages;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Nova\Nova;

class HomeController extends Controller
{
    /**
     * Show Nova homepage.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect(Nova::initialPathUrl($request));
    }
}
