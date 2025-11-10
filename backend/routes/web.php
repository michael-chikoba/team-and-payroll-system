<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Payroll System API',
        'version' => '1.0.0',
        'status' => 'operational'
    ]);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});

// Password reset routes (if using web views for password reset)
Route::get('/reset-password/{token}', function ($token) {
    return response()->json(['message' => 'Password reset token received']);
})->name('password.reset');

// Fallback route for undefined web routes
Route::fallback(function () {
    return response()->json([
        'message' => 'Route not found',
        'status' => 404
    ], 404);
});