<?php

use Illuminate\Support\Facades\Route;
use Laravel\Nova\Http\Controllers\ScriptController;
use Laravel\Nova\Http\Controllers\StyleController;

// Scripts & Styles...
Route::get('/scripts/{script}', ScriptController::class);
Route::get('/styles/{style}', StyleController::class);
