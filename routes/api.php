<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApacheLogController;

Route::middleware('api')->group(function () {
    Route::get('/logs', [ApacheLogController::class, 'index']);
    Route::post('/logs/upload', [ApacheLogController::class, 'upload']);
    Route::get('/logs/statistics', [ApacheLogController::class, 'statistics']);
}); 