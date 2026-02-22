<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GateController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

// ─── AUTH (Publik) ─────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class , 'login']);
Route::post('/register', [AuthController::class , 'register']);

// ─── PUBLIK: Event & Kursi ─────────────────────────────────────────────────
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class , 'index']);
    Route::get('/{slug}', [EventController::class , 'show']);
});

Route::get('/sessions/{sessionId}/seats', [SeatController::class , 'getSeatMap']);

// Seat Locking — dibuka sementara tanpa login
Route::post('/sessions/{sessionId}/lock-seat', [SeatController::class , 'lockSeat']);

// Checkout — dibuka sementara tanpa login
Route::post('/checkout', [OrderController::class , 'checkout']);

// ─── BUTUH LOGIN ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/user', [AuthController::class , 'me']);

    // ── ADMIN: CRUD Event & Sesi ───────────────────────────────────────────
    Route::prefix('admin')->group(function () {
            // Event
            Route::get('/events', [EventController::class , 'adminIndex']);
            Route::post('/events', [EventController::class , 'store']);
            Route::get('/events/{id}', [EventController::class , 'adminShow']);
            Route::put('/events/{id}', [EventController::class , 'update']);
            Route::delete('/events/{id}', [EventController::class , 'destroy']);

            // Sesi (nested under event)
            Route::get('/events/{eventId}/sessions', [SessionController::class , 'index']);
            Route::post('/events/{eventId}/sessions', [SessionController::class , 'store']);

            // Sesi (standalone by session ID)
            Route::get('/sessions/{sessionId}', [SessionController::class , 'show']);
            Route::put('/sessions/{sessionId}', [SessionController::class , 'update']);
            Route::delete('/sessions/{sessionId}', [SessionController::class , 'destroy']);
        }
        );

        // ── FINANCE: Kelola Order & Pembayaran ─────────────────────────────────
        Route::get('/finance/orders', [OrderController::class , 'financeIndex']);
        Route::post('/orders/{orderCode}/verify', [OrderController::class , 'verifyPayment']);

        // ── GATE: Scan QR ──────────────────────────────────────────────────────
        Route::post('/gate/scan', [GateController::class , 'scan']);
    });