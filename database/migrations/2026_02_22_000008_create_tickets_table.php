<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: tickets
 * Deskripsi: E-Ticket yang diterbitkan SETELAH pembayaran terverifikasi.
 *            Satu order_item menghasilkan satu tiket.
 *            Tiket berisi QR Code unik untuk scan saat masuk venue.
 *
 * SIKLUS STATUS TIKET:
 *   [issued] ──(Scan QR saat masuk venue, valid)──▶ [used]
 *   [issued] ──(Dibatalkan admin sebelum acara)───▶ [voided]
 *
 * Relasi:
 *   - tickets BELONGS TO orders
 *   - tickets BELONGS TO order_items (one-to-one)
 *   - tickets BELONGS TO users (pemesan)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->comment('Relasi ke order induk');

            $table->foreignId('order_item_id')
                ->unique()
                ->constrained('order_items')
                ->cascadeOnDelete()
                ->comment('Relasi 1-to-1 ke item order (1 kursi = 1 tiket)');

            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Pemilik/pemesan tiket ini');

            // ─── Data QR Code ──────────────────────────────────────────────
            $table->string('ticket_code', 60)->unique()
                ->comment('Kode unik tiket yang diencode menjadi QR Code. Format bebas (UUID/random string).');

            $table->string('qr_code_path')->nullable()
                ->comment('Path file gambar QR Code yang sudah di-generate (simpan di storage)');

            // ─── Snapshot Data Tiket (untuk ditampilkan di E-Ticket) ───────
            // Snapshot diperlukan agar tiket tetap valid walau data event diedit
            $table->string('event_name_snapshot')
                ->comment('Nama event saat tiket terbit');
            $table->string('session_name_snapshot')
                ->comment('Nama sesi (misal: Malam 1)');
            $table->date('event_date_snapshot')
                ->comment('Tanggal acara');
            $table->time('start_time_snapshot')
                ->comment('Jam mulai');
            $table->string('venue_snapshot')
                ->comment('Nama venue');
            $table->string('seat_code_snapshot', 20)
                ->comment('Kode kursi (misal: V1, A12)');
            $table->string('category_name_snapshot')
                ->comment('Nama kategori (misal: VIP, Reguler)');
            $table->decimal('price_paid_snapshot', 12, 2)
                ->comment('Harga tiket yang dibayar');

            // ─── Status Tiket ──────────────────────────────────────────────
            $table->enum('status', ['issued', 'used', 'voided'])
                ->default('issued')
                ->comment('issued=aktif & belum dipakai, used=sudah di-scan masuk, voided=tiket dibatalkan');

            // ─── Data Scan (Check-In) ──────────────────────────────────────
            $table->timestamp('used_at')->nullable()
                ->comment('Waktu tiket di-scan saat masuk venue');

            $table->foreignId('scanned_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Petugas gate (gate_officer) yang melakukan scan');

            // ─── Pengiriman ────────────────────────────────────────────────
            $table->timestamp('emailed_at')->nullable()
                ->comment('Waktu e-ticket dikirim via email');
            $table->timestamp('whatsapp_sent_at')->nullable()
                ->comment('Waktu e-ticket dikirim via WhatsApp');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ticket_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};