<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\GoogleAuthController;

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
    Route::post('/contact', [ContactFormController::class, 'store']);
    Route::get('auth/google/redirect',  [GoogleAuthController::class, 'redirect']);
    Route::get('auth/google/callback',  [GoogleAuthController::class, 'callback']);
