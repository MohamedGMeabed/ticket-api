<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'throttle:10,1']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);
Route::get('/events/{event}/seats', [EventController::class, 'seats']);

// Mock hosted payment page (for testing without real provider)
Route::get('/mock-checkout/{reference}', [PaymentController::class, 'mockCheckout'])->name('mock.checkout');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::post('/reservations/{reservation}/checkout', [PaymentController::class, 'checkout'])->name('checkout');
});

// Payment callbacks (called by payment provider after hosted checkout)
Route::get('/payments/success', [PaymentController::class, 'success']);
Route::get('/payments/cancel', [PaymentController::class, 'cancel']);

// Webhook endpoint for payment provider notifications
Route::post('/webhooks/payments', [PaymentController::class, 'webhook']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
