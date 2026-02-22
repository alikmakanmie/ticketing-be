<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: event_sessions
 * Deskripsi: Satu event dipecah menjadi beberapa sesi/jadwal.
 * Contoh: Event "Kancah Seni 2026" → Sesi "Malam 1", "Malam 2", "Malam 3"
 *
 * Relasi:
 *   - event_sessions BELONGS TO events (event_id)
 *   - event_sessions HAS MANY ticket_categories
 *   - event_sessions HAS MANY seats
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete()
                ->comment('Relasi ke event induk');

            $table->string('name')->comment('Nama sesi, misal: Malam 1, Hari Pertama, dsb');
            $table->date('event_date')->comment('Tanggal pelaksanaan sesi ini');
            $table->time('start_time')->comment('Jam mulai acara');
            $table->time('end_time')->nullable()->comment('Jam selesai acara (estimasi)');
            $table->integer('total_seats')->default(0)->comment('Total kursi yang tersedia di sesi ini');
            $table->integer('available_seats')->default(0)->comment('Sisa kursi yang belum terjual (di-update saat ada transaksi)');

            $table->enum('status', ['upcoming', 'open', 'sold_out', 'ongoing', 'ended'])
                ->default('upcoming')
                ->comment('upcoming=belum buka, open=penjualan aktif, sold_out=habis, ongoing=acara berlangsung, ended=selesai');

            $table->timestamps();

            $table->index(['event_id', 'event_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};