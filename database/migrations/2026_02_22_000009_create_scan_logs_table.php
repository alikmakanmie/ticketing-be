<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: scan_logs
 * Deskripsi: Audit log setiap percobaan scan QR Code di pintu masuk.
 *            Ini adalah tabel PENTING untuk anti-fraud.
 *
 * Semua percobaan scan dicatat, baik yang berhasil (success)
 * maupun yang gagal (already_used, invalid_code, wrong_event).
 *
 * Ini memungkinkan admin melihat:
 *   - Siapa yang mencoba masuk berkali-kali pakai tiket yang sama (calo)
 *   - Petugas mana yang melakukan scan
 *   - Jam berapa tiket digunakan
 *
 * Relasi:
 *   - scan_logs BELONGS TO tickets (nullable, jika QR code tidak valid)
 *   - scan_logs BELONGS TO users (scanned_by = petugas gate)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_code_scanned', 60)
                ->comment('Kode yang di-scan (raw dari QR). Disimpan sebagai string karena bisa saja kode tidak valid.');

            $table->foreignId('ticket_id')->nullable()
                ->constrained('tickets')
                ->nullOnDelete()
                ->comment('Ticket ID jika kode valid dan ditemukan di database. NULL jika kode tidak dikenal.');

            $table->foreignId('scanned_by')
                ->constrained('users')
                ->comment('Petugas gate yang melakukan scan');

            $table->enum('result', [
                'success', // ✅ Tiket valid, pertama kali digunakan → Akses diterima
                'already_used', // 🚨 Tiket sudah pernah di-scan → TOLAK, tampil merah
                'invalid_code', // ❌ Kode QR tidak dikenali / tidak ada di database
                'wrong_session', // ❌ Tiket valid tapi untuk sesi/acara yang berbeda
                'voided', // ❌ Tiket sudah di-void oleh admin
                'order_not_paid', // ❌ Tiket ada tapi ordernya belum lunas
            ])->comment('Hasil scan QR Code');

            $table->text('notes')->nullable()
                ->comment('Catatan tambahan, diisi sistem secara otomatis');

            $table->string('device_info')->nullable()
                ->comment('Info perangkat scanner (User-Agent browser/app)');

            $table->timestamps();

            $table->index('ticket_code_scanned');
            $table->index(['ticket_id', 'result']);
            $table->index('scanned_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};