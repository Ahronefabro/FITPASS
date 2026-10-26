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

Route::get('/photos/{file}', [MemberController::class, 'photo'])
    ->where('file', '[A-Za-z0-9]{40}\.(jpg|jpeg|png|webp)')
    ->name('member.photo');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [MemberController::class, 'show']);
    Route::put('/me/password', [MemberController::class, 'updatePassword']);
    Route::post('/me/photo', [MemberController::class, 'updatePhoto']);
    Route::delete('/me/photo', [MemberController::class, 'destroyPhoto']);

    Route::get('/check-ins', [MemberController::class, 'checkIns']);
    Route::get('/check-ins/latest', [MemberController::class, 'latestCheckIn']);
});
