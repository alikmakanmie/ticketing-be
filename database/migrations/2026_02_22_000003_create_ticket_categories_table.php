<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: ticket_categories
 * Deskripsi: Kategori tiket per sesi beserta harganya.
 *            Admin bisa mengedit harga kapan saja sebelum kursi terjual.
 *
 * Contoh:
 *   - Sesi "Malam 1" → Kategori "VIP"      = Rp 150.000
 *   - Sesi "Malam 1" → Kategori "Reguler"  = Rp  75.000
 *
 * Relasi:
 *   - ticket_categories BELONGS TO event_sessions (session_id)
 *   - ticket_categories HAS MANY seats (via category_id di tabel seats)
 *
 * Catatan penting untuk Frontend:
 *   - Harga diambil dari tabel ini saat user klik kursi (dinamis).
 *   - Frontend HARUS selalu request harga terbaru dari API,
 *     jangan simpan harga di local state terlalu lama.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('event_sessions')
                ->cascadeOnDelete()
                ->comment('Relasi ke sesi event');

            $table->string('name')->comment('Nama kategori, misal: VIP, VVIP, Reguler, Tribun');
            $table->string('color_hex', 7)->nullable()->comment('Warna untuk denah kursi di UI, misal: #FFD700 untuk VIP');
            $table->text('description')->nullable()->comment('Deskripsi fasilitas kategori ini');
            $table->decimal('price', 12, 2)->comment('Harga tiket kategori ini dalam Rupiah');
            $table->integer('quota')->default(0)->comment('Kuota/jumlah kursi total di kategori ini');
            $table->integer('available_quota')->default(0)->comment('Sisa kuota (di-update saat transaksi)');

            $table->boolean('is_active')->default(true)->comment('False = kategori ini sedang dinonaktifkan (tidak dijual)');

            $table->timestamps();

            $table->index(['session_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};