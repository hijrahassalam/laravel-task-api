<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]));

    // Auth routes (rate limited: 5 req/min)
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Tasks
        Route::patch('/tasks/batch-status', [TaskController::class, 'batchStatus']);
        Route::apiResource('tasks', TaskController::class);
        Route::post('/tasks/{task}/assign', [TaskController::class, 'assign']);
        Route::get('/tasks/{task}/activity', [TaskController::class, 'activity']);
    });
});
