<?php

use App\Http\Controllers\Api\AbandonedObjectController;
use Illuminate\Support\Facades\Route;

// все эти роуты будут доступны по префиксу /api
Route::get('/objects', [AbandonedObjectController::class, 'index']);
Route::get('/objects/{type?}', [AbandonedObjectController::class, 'byType']);
