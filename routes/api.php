<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

/*
| The mobile app for members. Everything answers in json and is reached under
| /api. A member logs in for a token and sends it as a bearer token after that.
*/

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [MemberController::class, 'show']);
    Route::put('/me/password', [MemberController::class, 'updatePassword']);

    Route::get('/check-ins', [MemberController::class, 'checkIns']);
    Route::get('/check-ins/latest', [MemberController::class, 'latestCheckIn']);
});
