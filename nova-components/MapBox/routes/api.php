<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\v1\BusinessesController;

Route::get('/places', function () {
    return response()->json(url('/api/v1/places/geo-json'));
});

Route::get('/business-draw', [BusinessesController::class, 'fetchJson']);
