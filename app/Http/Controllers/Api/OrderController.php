<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * FASE 3: Checkout - "Pesan & Bayar" Pembeli
     * Mengubah state "Locked" ke "Pending Payment" / Terbitnya Order Header
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:event_sessions,id',
            'seat_id' => 'required|exists:seats,id',
            // Opsi metode bayar, dll
            'payment_method' => 'required|string|in:bank_transfer,midtrans',
        ]);

        $user = auth()->user();

        // Transaction untuk keamanan Snapshot dan State
        DB::beginTransaction();
        try {
            // Ambil data kursi beserta kategori (untuk harga terbaru)
            // Di sini kita TIDAK lockForUpdate karena user SUDAH menguncinya di tahap sebelumnya.
            $seat = Seat::with('category', 'session')->findOrFail($request->seat_id);

            // Validasi: Apakah benar-benar masih dikunci oleh User ini?
            if (!$seat->isLockedByUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi kunci kursi Anda telah kadaluwarsa. Ulangi proses pemilihan kursi.',
                ], 400); // Bad Request (Waktu Habis)
            }

            // Hitung harga dari snapshot category (sekarang)
            $subtotal = $seat->category->price;
            $serviceFee = 2500; // Contoh statis: Biaya Platform
            $totalAmount = $subtotal + $serviceFee;

            // Generate Order Header
            $order = Order::create([
                'user_id' => $user->id,
                'session_id' => $seat->session_id,
                'order_code' => 'TKT-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_deadline' => Carbon::parse($seat->locked_until), // Sama dengan waktu habsnya kursi
            ]);

            // Copy data snapshot ke Order Item agar tak terdampak jika Admin edit harga nanti
            OrderItem::create([
                'order_id' => $order->id,
                'seat_id' => $seat->id,
                'category_id' => $seat->category_id,
                'category_name_snapshot' => $seat->category->name,
                'price_snapshot' => $seat->category->price,
                'seat_code_snapshot' => $seat->seat_code,
            ]);

            // Siapkan Header OrderPayment (Pending state)
            OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'status' => OrderPayment::STATUS_PENDING,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat. Lanjutkan ke proses pembayaran.',
                'data' => [
                    'order_code' => $order->order_code,
                    'total_amount' => $totalAmount,
                    'payment_deadline' => $order->payment_deadline,
                ]
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses checkout.',
            ], 500);
        }
    }

    /**
     * FASE 3: Pembayaran & Konfirmasi (Admin Verifikasi)
     * Misal admin Fionna menekan tombol "Verifikasi / Approve" transfer bank user.
     * (Atau Webhook otomatis Middleware)
     */
    public function verifyPayment(Request $request, $orderCode)
    {
        // 1. Validasi Akses: Admin Keuangan yg boleh (Fionna)
        if (!auth()->user()->isFinance() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Dibutuhkan role Finance.',
            ], 403);
        }

        $order = Order::with(['items.seat', 'session.event', 'payment'])->where('order_code', $orderCode)->firstOrFail();

        // 2. Cegah Verifikasi Uang jika Order Expired/Cancel
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return response()->json([
                'success' => false,
                'message' => 'Status Order ini bukan Pending Payment.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // A. Update Status Order -> Paid (Sukses)
            $order->status = Order::STATUS_PAID;
            $order->paid_at = Carbon::now();
            $order->verified_by = auth()->user()->id; // Catat siapa yg ng-approve
            $order->save();

            // Memperbarui order payment status
            if ($order->payment) {
                $order->payment->status = OrderPayment::STATUS_VERIFIED;
                $order->payment->verified_at = Carbon::now();
                $order->payment->save();
            }

            // B. Mengubah Kursi -> Booked (Permanen Lunas / Tidak Available Lagi)
            foreach ($order->items as $item) {
                $seat = $item->seat;
                $seat->status = Seat::STATUS_BOOKED; // Terjual selamanya
                $seat->save();

                // C. Penerbitan E-Ticket & QR Generation
                Ticket::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'user_id' => $order->user_id,
                    'ticket_code' => 'QR-' . strtoupper(Str::uuid()->toString()), // Data unik untuk isi dari QR Code
                    'status' => Ticket::STATUS_ISSUED,

                    // Snapshot data tiket lengkap menghindari admin ganti jadwal besoknya
                    'event_name_snapshot' => $order->session->event->name,
                    'session_name_snapshot' => $order->session->name,
                    'event_date_snapshot' => $order->session->event_date,
                    'start_time_snapshot' => $order->session->start_time,
                    'venue_snapshot' => $order->session->event->venue,
                    'seat_code_snapshot' => $item->seat_code_snapshot,
                    'category_name_snapshot' => $item->category_name_snapshot,
                    'price_paid_snapshot' => $item->price_snapshot,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran terverifikasi! E-Ticket berhasil diterbitkan.',
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal verifikasi payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}