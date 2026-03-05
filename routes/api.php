<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HuggingFaceController;
use Illuminate\Support\Facades\Validator;

Route::middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [GoogleAuthController::class, 'logout']);
    Route::post('auth/update-profile', [GoogleAuthController::class, 'updateProfile']);
    Route::get('/threads', [ThreadController::class, 'index']);
    Route::post('/threads', [ThreadController::class, 'store']);
    Route::get('/threads/{id}', [ThreadController::class, 'show']);
    Route::put('/threads/{id}', [ThreadController::class, 'update']);
    Route::delete('/threads/{id}', [ThreadController::class, 'destroy']);
    Route::post('/threads/{id}/message', [ThreadController::class, 'sendMessage']);
    Route::post('/threads/{id}/response', [ThreadController::class, 'storeResponse']);
    Route::post('/messages/send', [ThreadController::class, 'sendMessage']);
});
Route::post('/generate-text', [HuggingFaceController::class, 'generateText']);
Route::post('/generate-image', [HuggingFaceController::class, 'generateImage']);

Route::post('/contact', [ContactFormController::class, 'store']);
Route::get('/get-contact', [ContactFormController::class, 'GetContact']);
Route::post('/auth/login', [GoogleAuthController::class, 'login']);
