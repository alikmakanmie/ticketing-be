<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: orders
 * Deskripsi: Header transaksi pemesanan. Dibuat saat user menekan "Pesan / Lanjut Bayar".
 *
 * SIKLUS STATUS ORDER (State Machine):
 *
 *   [pending_payment]
 *       │
 *       ├──(Bayar, Admin verifikasi / Webhook)──▶ [paid] ──▶ (E-Ticket diterbitkan)
 *       │
 *       └──(15 menit habis, tidak bayar)──────▶ [expired]
 *              └──(Status kursi dikembalikan ke 'available')
 *
 *   [paid] juga bisa di-refund → [refunded]
 *
 * Relasi:
 *   - orders BELONGS TO users (user_id)
 *   - orders BELONGS TO event_sessions (session_id)
 *   - orders HAS ONE order_payment (bukti bayar / detail gateway)
 *   - orders HAS MANY order_items (detail per kursi yang dibeli)
 *   - orders HAS MANY tickets (E-Ticket yang diterbitkan)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Pembeli / pemesan');

            $table->foreignId('session_id')
                ->constrained('event_sessions')
                ->comment('Sesi event yang dipesan');

            // ─── Identitas Order ───────────────────────────────────────────
            $table->string('order_code', 30)->unique()
                ->comment('Kode order unik yang ditampilkan ke user, misal: TKT-2026-0001');

            $table->decimal('subtotal', 12, 2)
                ->comment('Total harga sebelum diskon/biaya');
            $table->decimal('service_fee', 12, 2)->default(0)
                ->comment('Biaya layanan/admin');
            $table->decimal('total_amount', 12, 2)
                ->comment('Total akhir yang harus dibayar user = subtotal + service_fee');

            // ─── State Machine ─────────────────────────────────────────────
            $table->enum('status', [
                'pending_payment', // Menunggu pembayaran (kursi terkunci, timer aktif)
                'paid', // Pembayaran terverifikasi, E-Ticket sudah terbit
                'expired', // Waktu bayar habis, kursi dibebaskan kembali
                'cancelled', // Dibatalkan oleh admin/sistem
                'refunded', // Dana sudah dikembalikan ke pembeli
            ])->default('pending_payment');

            // ─── Timer Countdown ───────────────────────────────────────────
            $table->timestamp('payment_deadline')
                ->comment('Batas waktu pembayaran. Biasanya = created_at + 15 menit. Job scheduler akan sweep setelah waktu ini.');

            // ─── Data Pembayaran (untuk manual transfer) ───────────────────
            $table->timestamp('paid_at')->nullable()
                ->comment('Timestamp kapan order ini di-approve/verified');
            $table->foreignId('verified_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin keuangan yang melakukan verifikasi (null jika otomatis via gateway)');

            // ─── Catatan ───────────────────────────────────────────────────
            $table->text('notes')->nullable()
                ->comment('Catatan internal untuk admin');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_deadline'); // Dipakai Job untuk cari order expired
            $table->index('order_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};