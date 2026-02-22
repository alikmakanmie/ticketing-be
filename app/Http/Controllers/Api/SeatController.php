<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSession;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    /**
     * FASE 2: Eksplorasi - Membuka Denah Kursi (Seat Map)
     * Frontend merender posisi dan warna kursi berdasarkan status (Available/Locked/Booked).
     */
    public function getSeatMap($sessionId)
    {
        // Pastikan sesi ada dan masih 'open'
        $session = EventSession::with(['ticketCategories' => function ($q) {
            $q->where('is_active', true);
        }])->findOrFail($sessionId);

        if ($session->status !== EventSession::STATUS_OPEN) {
            return response()->json([
                'success' => false,
                'message' => 'Penjualan tiket untuk sesi ini sedang ditutup.',
            ], 403);
        }

        // Ambil semua kursi milik sesi ini
        $seats = Seat::where('session_id', $sessionId)
            ->with('category:id,name,price,color_hex')
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        // Bersihkan state "stale" di layer view (Misal ada kursi locked tapi waktunya habis, kembalikan json 'available')
        // Ini tidak update database, tapi mapping ulang response JSOn secara realtime
        $seats = $seats->map(function ($seat) {
            if ($seat->status === 'locked' && $seat->locked_until < Carbon::now()) {
                $seat->status = 'available'; // Overwrite untuk frontend
            }
            return [
            'id' => $seat->id,
            'seat_code' => $seat->seat_code,
            'category_id' => $seat->category_id,
            'category_name' => collect($seat->category)->get('name'),
            'price' => collect($seat->category)->get('price'),
            'color_hex' => collect($seat->category)->get('color_hex'),
            'status' => $seat->status,
            'locked_until' => $seat->locked_until,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Denah Kursi ' . $session->name,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'event_date' => $session->event_date->format('Y-m-d'),
                ],
                'categories' => $session->ticketCategories,
                'seats' => $seats->groupBy('row_label'), // Grup berdasarkan Baris (Misal: baris V, A, B)
            ]
        ]);
    }

    /**
     * FASE 2: Pemilihan Kursi - "Seat Locking" (Race Condition mitigation)
     */
    public function lockSeat(Request $request, $sessionId)
    {
        // 1. Validasi Input (Support array seat_ids dari frontend)
        $request->validate([
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        // Fallback user ID 1 (Karena anda belum implementasi Login di Frontend)
        $userId = auth()->user() ? auth()->user()->id : 1;

        // 2. Transaction mencegah Race Condition (Locking Pessimistic - lockForUpdate).
        try {
            DB::beginTransaction();

            // Looping seluruh seat_ids untuk mencegah race condition
            $lockedSeats = [];
            foreach ($request->seat_ids as $seatId) {
                $seat = Seat::where('id', $seatId)
                    ->where('session_id', $sessionId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3. Pengecekan status akhir secara akurat (apakah beneran kosong)
                if (!$seat->isAvailable()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Maaf, kursi {$seat->seat_code} baru saja diambil orang lain.",
                        'error' => 'seat_unavailable'
                    ], 409); // 409 Conflict
                }

                // Masukkan ke array batch update
                $lockedSeats[] = $seat;
            }

            // 4. Sukses: Backend mengubah status kursi jadi 'Locked' 
            foreach ($lockedSeats as $seatToLock) {
                $seatToLock->status = Seat::STATUS_LOCKED;
                $seatToLock->locked_by = $userId;
                $seatToLock->locked_until = Carbon::now()->addMinutes(15);
                $seatToLock->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kursi berhasil dikunci. Anda memiliki waktu 15 Menit untuk Checkout.',
                'data' => [
                    'locked_seats_count' => count($lockedSeats),
                    'locked_until' => Carbon::now()->addMinutes(15)->toIso8601String(),
                ]
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses kursi.',
            ], 500);
        }
    }
}