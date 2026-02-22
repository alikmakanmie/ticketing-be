<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: order_items
 * Deskripsi: Detail baris per kursi di dalam sebuah order.
 *            Satu order bisa memiliki banyak kursi (1 order_item = 1 kursi).
 *
 * Kenapa harga di-snapshot di sini?
 *   → Karena Admin bisa mengubah harga kategori kapan saja.
 *     Kita perlu menyimpan harga saat transaksi terjadi (price_snapshot)
 *     agar histori keuangan tetap akurat meskipun harga berubah.
 *
 * Relasi:
 *   - order_items BELONGS TO orders
 *   - order_items BELONGS TO seats
 *   - order_items BELONGS TO ticket_categories (untuk referensi)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete()
                ->comment('Relasi ke header order');

            $table->foreignId('seat_id')
                ->constrained('seats')
                ->comment('Kursi spesifik yang dibeli');

            $table->foreignId('category_id')
                ->constrained('ticket_categories')
                ->comment('Kategori kursi saat pembelian');

            // ─── Price Snapshot ────────────────────────────────────────────
            $table->string('category_name_snapshot')
                ->comment('Nama kategori saat transaksi (snapshot, tidak berubah meski kategori diedit)');
            $table->decimal('price_snapshot', 12, 2)
                ->comment('Harga satuan saat transaksi terjadi (snapshot)');
            $table->string('seat_code_snapshot', 20)
                ->comment('Kode kursi saat transaksi (snapshot)');

            $table->timestamps();

            $table->unique('seat_id', 'unique_seat_per_order');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};