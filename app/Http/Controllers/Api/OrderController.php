<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
     * FASE 3: Checkout — POST /api/checkout
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'session_id'     => 'required|exists:event_sessions,id',
            'seat_ids'       => 'required|array',
            'seat_ids.*'     => 'exists:seats,id',
            'payment_method' => 'required|string|in:bank_transfer,midtrans',
        ]);

        $userId = auth()->user() ? auth()->user()->id : 1;

        DB::beginTransaction();
        try {
            $subtotal   = 0;
            $orderItems = [];
            $deadline   = null;

            foreach ($request->seat_ids as $seatId) {
                $seat = Seat::with('category')->findOrFail($seatId);

                if ($userId !== 1 && !$seat->isLockedByUser($userId)) {
                    return response()->json(['success' => false, 'message' => 'Status kunci kursi tidak valid.'], 400);
                }

                $subtotal    += $seat->category->price;
                $orderItems[] = $seat;
                $deadline     = $seat->locked_until;
            }

            $serviceFee  = 2500;
            $totalAmount = $subtotal + $serviceFee;

            $order = Order::create([
                'user_id'          => $userId,
                'session_id'       => $request->session_id,
                'order_code'       => 'TKT-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'subtotal'         => $subtotal,
                'service_fee'      => $serviceFee,
                'total_amount'     => $totalAmount,
                'status'           => Order::STATUS_PENDING_PAYMENT,
                'payment_deadline' => Carbon::parse($deadline ?? Carbon::now()->addMinutes(15)),
            ]);

            foreach ($orderItems as $seat) {
                \App\Models\OrderItem::create([
                    'order_id'               => $order->id,
                    'seat_id'                => $seat->id,
                    'category_id'            => $seat->category_id,
                    'category_name_snapshot' => $seat->category->name,
                    'price_snapshot'         => $seat->category->price,
                    'seat_code_snapshot'     => $seat->seat_code,
                ]);
            }

            OrderPayment::create([
                'order_id'       => $order->id,
                'payment_method' => $request->payment_method,
                'status'         => OrderPayment::STATUS_PENDING,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat.',
                'data'    => [
                    'order_code'       => $order->order_code,
                    'total_amount'     => $totalAmount,
                    'payment_deadline' => $order->payment_deadline,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Kesalahan checkout: ' . $e->getMessage()], 500);
        }
    }

    /**
     * FINANCE: Daftar semua order — GET /api/finance/orders
     */
    public function financeIndex(Request $request)
    {
        $user = auth()->user();
        if (!$user?->isFinance() && !$user?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $orders = Order::with(['user:id,name,email', 'session.event:id,name', 'payment', 'items'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    /**
     * FINANCE: Verifikasi pembayaran — POST /api/orders/:orderCode/verify
     */
    public function verifyPayment(Request $request, $orderCode)
    {
        if (!auth()->user()?->isFinance() && !auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Dibutuhkan role Finance.'], 403);
        }

        $order = Order::with(['items.seat', 'session.event', 'payment'])->where('order_code', $orderCode)->firstOrFail();

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return response()->json(['success' => false, 'message' => 'Status Order ini bukan Pending Payment.'], 400);
        }

        DB::beginTransaction();
        try {
            $order->status      = Order::STATUS_PAID;
            $order->paid_at     = Carbon::now();
            $order->verified_by = auth()->id();
            $order->save();

            if ($order->payment) {
                $order->payment->status      = OrderPayment::STATUS_VERIFIED;
                $order->payment->verified_at = Carbon::now();
                $order->payment->save();
            }

            foreach ($order->items as $item) {
                $seat         = $item->seat;
                $seat->status = Seat::STATUS_BOOKED;
                $seat->save();

                Ticket::create([
                    'order_id'               => $order->id,
                    'order_item_id'          => $item->id,
                    'user_id'                => $order->user_id,
                    'ticket_code'            => 'QR-' . strtoupper(Str::uuid()->toString()),
                    'status'                 => Ticket::STATUS_ISSUED,
                    'event_name_snapshot'    => $order->session->event->name,
                    'session_name_snapshot'  => $order->session->name,
                    'event_date_snapshot'    => $order->session->event_date,
                    'start_time_snapshot'    => $order->session->start_time,
                    'venue_snapshot'         => $order->session->event->venue,
                    'seat_code_snapshot'     => $item->seat_code_snapshot,
                    'category_name_snapshot' => $item->category_name_snapshot,
                    'price_paid_snapshot'    => $item->price_snapshot,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembayaran terverifikasi! E-Ticket diterbitkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Kesalahan verifikasi: ' . $e->getMessage()], 500);
        }
    }
}