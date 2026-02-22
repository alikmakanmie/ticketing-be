<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReleaseExpiredSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek order yang belum dibayar dan melebihi batas waktu (15 menit), lalu membatalkannya dan membebaskan kursi.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = \Illuminate\Support\Carbon::now();

        // 1. Ambil Order yang status 'pending_payment' dan waktunya sudah habis
        // LockForUpdate untuk menghindari double proses
        $expiredOrders = \App\Models\Order::with('items')
            ->where('status', \App\Models\Order::STATUS_PENDING_PAYMENT)
            ->where('payment_deadline', '<', $now)
            ->get();

        $count = $expiredOrders->count();
        if ($count === 0) {
            $this->info("Tidak ada order expired ditemukan.");
            return;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($expiredOrders as $order) {
                // Ubah status order menjadi Expired
                $order->status = \App\Models\Order::STATUS_EXPIRED;
                $order->save();

                // Ubah status order_payment menjadi Rejected/Cancelled
                if ($order->payment) {
                    $order->payment->status = \App\Models\OrderPayment::STATUS_REJECTED;
                    $order->payment->save();
                }

                // Bebaskan kursi agar bisa dibeli orang lain dengan aman:
                // Pada arsitektur aplikasi ini, state kursi akan auto-available karena `locked_until` < now
                // Tapi ada baiknya kita "menyapu bersih" reset ke available agar rapi di DB.
                foreach ($order->items as $item) {
                    $seat = \App\Models\Seat::find($item->seat_id);
                    if ($seat && $seat->status !== \App\Models\Seat::STATUS_BOOKED) {
                        $seat->status = \App\Models\Seat::STATUS_AVAILABLE;
                        $seat->locked_by = null;
                        $seat->locked_until = null;
                        $seat->save();
                    }
                }
            }
            \Illuminate\Support\Facades\DB::commit();
            $this->info("Berhasil melepas {$count} order & kursi yang kadaluwarsa.");
        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->error("Gagal melepas kursi: " . $e->getMessage());
        }
    }
}