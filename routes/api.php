<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GateController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SeatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── FASE 1 & FASE 2: Eksplorasi (Bisa diakses tanpa login) ────────────────
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class , 'index']); // List Event Aktif
    Route::get('/{slug}', [EventController::class , 'show']); // Buka Detail Event
});

Route::prefix('sessions')->group(function () {
    Route::get('/{sessionId}/seats', [SeatController::class , 'getSeatMap']); // Lihat Denah Kursi per Sesi
});


// ─── FASE 2, 3 & 4: Membutuhkan Login User (Sanctum) ──────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Seat Locking oleh User
    Route::post('/sessions/{sessionId}/lock-seat', [SeatController::class , 'lockSeat']);

    // Checkout Order oleh User
    Route::post('/checkout', [OrderController::class , 'checkout']);

    // Verifikasi Pembayaran (Fionna / System Middleware) -> Butuh role Admin/Finance (Divalidasi di Controller)
    Route::post('/orders/{orderCode}/verify', [OrderController::class , 'verifyPayment']);

    // Scan QR Code di Gate masuk -> Butuh role Gate Officer (Divalidasi di Controller)
    Route::post('/gate/scan', [GateController::class , 'scan']);

});