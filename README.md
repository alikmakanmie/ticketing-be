# Backend Tiket.in - E-Ticketing System

Sistem Backend untuk aplikasi pemesanan tiket event/konser, dibangun menggunakan framework **Laravel 11** dan **SQLite**. Proyek ini mendemonstrasikan penanganan inti dari *Business Logic* ticketing, mulai dari eksplorasi event, pemilihan kursi, manajemen Checkout, pembayaran, hingga QR Code Check-in di *Venue Gate*.

## 🌟 Fitur Utama (Core Features)

1.  **State Machine Kursi (Seat Management):** Kursi memiliki 4 status akurat (`available`, `locked`, `booked`, `used`).
2.  **Mitigasi Race Condition (Pessimistic Locking):** Menggunakan fitur `DB::lockForUpdate()` untuk mengamankan kursi (*lock*) saat ada beberapa *user* mencoba membeli satu kursi yang sama pada waktu sepersekian detik yang bersamaan.
3.  **Audit Trail & Anti-Fraud Scanner:** Sistem gate scanner yang divalidasi dengan tabel Riwayat Scan `scan_logs` untuk mencegah penggunaan satu tiket oleh *calo* atau *screenshot* tiket berulang kali.
4.  **Sistem Snapshot Harga:** Sistem tiket statis; harga tiket yang dibeli (*snapshot*) disalin ke data Order dan E-Ticket `tickets` untuk menjaga konsistensi laporan akuntansi meskipun admin mengganti harga kategori asilnya di masa depan.
5.  **Task Scheduler Latar Belakang:** Dilengkapi program pembersihan otomatis (`php artisan schedule:run`) yang berjalan tiap menit untuk merilis kembali daftar kursi `locked`/`pending_payment` yang melewati batas waktu pembayaran 15 menit.

## 🚀 Instalasi & Cara Menjalankan (Local Development)

### Persyaratan Sistem
*   PHP 8.2 atau lebih baru
*   Ekstensi PHP: `pdo`, `sqlite3` (Untuk Linux: `sudo dnf install php-pdo php-sqlite3`)
*   Composer

### Langkah-langkah
1.  **Clone / Download Repository ini**
2.  **Install Dependensi Composer**
    ```bash
    composer install
    ```
3.  **Copy Environment Variables**
    ```bash
    cp .env.example .env
    ```
4.  **Generate App Key**
    ```bash
    php artisan key:generate
    ```
5.  **Siapkan Database & Jalankan Migrasi + Seeder**
    Pastikan file `database/database.sqlite` sudah ada (atau kosongkan isinya).
    ```bash
    touch database/database.sqlite
    php artisan migrate:fresh --seed
    ```
6.  **Jalankan Server Laravel**
    ```bash
    php artisan serve
    ```
7.  **Jalankan Background Task Scheduler (Dibutuhkan untuk expire seat otomatis)**
    Buka tab/terminal baru dan jalankan:
    ```bash
    php artisan schedule:work
    ```

## 📊 Akun Login Dummy (Hasil Seeder)

Password untuk **semua akun** di bawah ini adalah: `password`

| Nama | Email | Role |
| :--- | :--- | :--- |
| **Super Admin** | `admin@tiket.in` | `admin` |
| **Fionna Finance** | `finance@tiket.in` | `finance` |
| **Budi Gate Officer** | `gate@tiket.in` | `gate_officer` |
| **Andi Pembeli** | `buyer@tiket.in` | `buyer` |

Ketika project pertama kali di-seed, *Database* akan otomatis men-generate **1 buah Event ("Kancah Seni 2026")**, **3 Sesi (Malam 1-3)**,  **300 Kursi (VIP & Reguler)** per sesi, serta **Transaksi Dummy (4 Kursi terkunci, 6 Terbeli Lunas)** di Sesi Malam 1 agar Frontend bisa melihat variasi grid denah tempat duduk secara instan.

## 📡 Daftar API Endpoints Utama

Seluruh endpoint diawali dengan base URL: `http://localhost:8000/api`

### A. Public Routes (Tanpa Auth)
*   `GET /events` - Mendapatkan daftar acara keseluruhan
*   `GET /events/{slug}` - Mendapatkan detail satu acara beserta daftar sesi (mendapatkan `sessionId`)
*   `GET /sessions/{sessionId}/seats` - Mengambil Denah Pemetaan Kursi dan statusnya secara akurat

### B. Transaksi Pembeli (Header: `Authorization: Bearer <token_buyer>`)
*   `POST /sessions/{sessionId}/lock-seat` - Mengunci kursi dari pembeli lain *(Body: `seat_id`)*
*   `POST /checkout` - Konfirmasi nominal Checkout dan metode bayar *(Body: `session_id`, `seat_id`, `payment_method`)*

### C. Admin & Gate (Header: `Authorization: Bearer <token_admin/gate>`)
*   `POST /orders/{orderCode}/verify` - (Finance) Verifikasi pembayaran manual untuk mengubah Seat & Menerbitkan E-Ticket.
*   `POST /gate/scan` - (Gate Officer) Membaca QR Code untuk merekam jejak Check-in masuk gerbang *(Body: `qr_code`, `device_id`)*.

---
*Dikembangkan dengan ❤️ untuk Project E-Ticketing*
