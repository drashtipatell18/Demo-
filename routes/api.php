<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\GoogleAuthController;

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [GoogleAuthController::class, 'logout']);
        Route::post('auth/update-profile', [GoogleAuthController::class, 'updateProfile']);
    })->middleware('auth:sanctum');
    Route::post('/contact', [ContactFormController::class, 'store']);
    Route::get('/get-contact', [ContactFormController::class, 'GetContact']);
    Route::get('auth/google/redirect',  [GoogleAuthController::class, 'redirect']);
    Route::get('auth/google/callback',  [GoogleAuthController::class, 'callback']);
