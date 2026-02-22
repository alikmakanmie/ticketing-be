<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: order_payments
 * Deskripsi: Detail bukti pembayaran / data dari Payment Gateway.
 *            Satu order memiliki tepat satu data payment.
 *
 * Mendukung DUA skenario:
 *   1. MANUAL: User upload bukti transfer → Admin finance verifikasi secara manual.
 *   2. GATEWAY: Midtrans/Xendit/dll mengirim Webhook ke Backend → Otomatis approve.
 *
 * Relasi:
 *   - order_payments BELONGS TO orders (one-to-one)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete()
                ->comment('Relasi 1-to-1 ke order');

            $table->enum('payment_method', [
                'bank_transfer', // Transfer manual
                'midtrans', // Payment gateway Midtrans
                'xendit', // Payment gateway Xendit
                'cash', // Bayar tunai di kasir
                'other', // Metode lain
            ])->comment('Metode pembayaran yang dipilih user');

            // ─── Data Manual Transfer ──────────────────────────────────────
            $table->string('bank_name')->nullable()
                ->comment('Nama bank pengirim (misal: BCA, Mandiri) jika transfer manual');
            $table->string('account_number', 30)->nullable()
                ->comment('Nomor rekening pengirim');
            $table->string('account_name')->nullable()
                ->comment('Nama pemilik rekening pengirim');
            $table->string('transfer_proof')->nullable()
                ->comment('Path file foto/scan bukti transfer yang di-upload user');
            $table->timestamp('transferred_at')->nullable()
                ->comment('Tanggal & jam transfer (diisi user saat upload bukti)');

            // ─── Data Payment Gateway (Midtrans/Xendit/dll) ────────────────
            $table->string('gateway_transaction_id')->nullable()
                ->comment('Transaction ID dari Payment Gateway (misal: order-TKT-2026-0001)');
            $table->string('gateway_payment_type')->nullable()
                ->comment('Tipe pembayaran di gateway (gopay, bca_va, credit_card, dll)');
            $table->json('gateway_response')->nullable()
                ->comment('Raw response JSON dari gateway untuk audit trail');
            $table->string('gateway_va_number')->nullable()
                ->comment('Nomor Virtual Account jika payment via VA');

            // ─── Status Pembayaran ─────────────────────────────────────────
            $table->enum('status', [
                'pending', // Belum ada konfirmasi
                'verified', // Sudah terkonfirmasi (baik manual maupun otomatis)
                'rejected', // Ditolak admin (bukti transfer salah/tidak sesuai)
            ])->default('pending');

            $table->decimal('amount_paid', 12, 2)->nullable()
                ->comment('Nominal yang dibayarkan (bisa berbeda dari total_amount jika salah transfer)');

            $table->timestamp('verified_at')->nullable()
                ->comment('Waktu verifikasi dilakukan');

            $table->timestamps();

            $table->index('status');
            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};