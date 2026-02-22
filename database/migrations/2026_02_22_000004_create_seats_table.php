<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: seats
 * Deskripsi: INTI dari sistem tiket. Setiap baris = 1 kursi fisik di 1 sesi.
 *            Admin men-generate ~300 kursi sekaligus (Bulk Insert) via API.
 *
 * SIKLUS STATUS KURSI (State Machine):
 *
 *   [available] ──(user pilih & klik Pesan)──▶ [locked]
 *       ▲                                           │
 *       │                                           │ Sukses Bayar
 *       │ Waktu habis / Expired                     ▼
 *       └──────────────────────────────────── [booked] ──▶ [used] (saat scan QR)
 *
 *   - available : Kursi bebas, tampil hijau di denah
 *   - locked    : Kursi sedang dikunci user lain (15 menit), tampil abu-abu
 *   - booked    : Kursi sudah dibayar lunas, tampil merah / tidak bisa dipilih
 *   - used      : Tiket sudah di-scan saat masuk venue
 *
 * Relasi:
 *   - seats BELONGS TO event_sessions (session_id)
 *   - seats BELONGS TO ticket_categories (category_id)
 *   - seats HAS ONE order_item (via seat_id)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('event_sessions')
                ->cascadeOnDelete()
                ->comment('Kursi ini milik sesi event yang mana');

            $table->foreignId('category_id')
                ->constrained('ticket_categories')
                ->comment('Kategori kursi ini (VIP/Reguler/dll)');

            $table->string('seat_code', 20)
                ->comment('Kode unik kursi, misal: V1, V2, A1, A2, B10');

            $table->string('row_label', 10)->nullable()
                ->comment('Label baris, misal: V, A, B, C');

            $table->smallInteger('seat_number')->nullable()
                ->comment('Nomor urut kursi dalam satu baris');

            // ─── State Machine Utama ───────────────────────────────────────
            $table->enum('status', ['available', 'locked', 'booked', 'used'])
                ->default('available')
                ->comment('State machine kursi. Lihat komentar di atas untuk alur lengkap.');

            // ─── Data Kunci (Locking) ──────────────────────────────────────
            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User ID yang sedang mengunci kursi ini');

            $table->timestamp('locked_until')
                ->nullable()
                ->comment('Waktu berakhirnya kunci (biasanya +15 menit dari waktu klik Pesan)');

            $table->timestamps();

            // ─── Index untuk Performa Query ────────────────────────────────
            $table->unique(['session_id', 'seat_code'], 'unique_seat_per_session');
            $table->index(['session_id', 'status']);
            $table->index('status');
            $table->index('locked_until'); // Dipakai oleh Job pembersih kursi expired
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};