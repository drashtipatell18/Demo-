<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Validator;

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [GoogleAuthController::class, 'logout']);
        Route::post('auth/update-profile', [GoogleAuthController::class, 'updateProfile']);
        Route::get('/threads', [ThreadController::class, 'index']);          // Get all threads
        Route::post('/threads', [ThreadController::class, 'store']);         // Create thread + first message
        Route::get('/threads/{id}', [ThreadController::class, 'show']);      // Get single thread messages
        Route::put('/threads/{id}', [ThreadController::class, 'update']);    // Update thread title
        Route::delete('/threads/{id}', [ThreadController::class, 'destroy']); // Delete thread
        Route::post('/threads/{id}/message', [ThreadController::class, 'sendMessage']);     // user message
        Route::post('/threads/{id}/response', [ThreadController::class, 'storeResponse']);
        Route::post('/messages/send', [ThreadController::class, 'sendMessage']); // model response
    });

    Route::post('/contact', [ContactFormController::class, 'store']);
    Route::get('/get-contact', [ContactFormController::class, 'GetContact']);
    Route::post('/auth/login', [GoogleAuthController::class, 'login']);
