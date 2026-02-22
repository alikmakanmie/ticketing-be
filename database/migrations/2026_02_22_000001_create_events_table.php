<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: events
 * Deskripsi: Menyimpan data event utama / kampanye acara.
 *            Satu event bisa punya banyak sesi (sessions).
 * Contoh: "Kancah Seni 2026"
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')
                ->constrained('users')
                ->comment('Admin yang membuat event ini');

            $table->string('name')->comment('Nama event, misal: Kancah Seni 2026');
            $table->string('slug')->unique()->comment('URL-friendly name, misal: kancah-seni-2026');
            $table->text('description')->nullable()->comment('Deskripsi lengkap event');
            $table->string('poster')->nullable()->comment('Path gambar poster event');
            $table->string('venue')->nullable()->comment('Nama gedung/lokasi');
            $table->text('venue_address')->nullable()->comment('Alamat lengkap venue');
            $table->string('city', 100)->nullable()->comment('Kota penyelenggaraan');
            $table->decimal('latitude', 10, 7)->nullable()->comment('Koordinat GPS latitude');
            $table->decimal('longitude', 10, 7)->nullable()->comment('Koordinat GPS longitude');

            $table->enum('status', ['draft', 'published', 'ended', 'cancelled'])
                ->default('draft')
                ->comment('draft=belum dipublikasi, published=aktif, ended=selesai, cancelled=dibatalkan');

            $table->timestamps();
            $table->softDeletes()->comment('Soft delete agar histori event tersimpan');

            $table->index('status');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};