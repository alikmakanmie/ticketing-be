# 🎟️ TiketIn — Backend (Laravel)

REST API backend untuk sistem pembelian tiket event, dibangun dengan **Laravel 11 + Sanctum** untuk autentikasi berbasis token.

---

## 🖥️ Tech Stack

| Teknologi | Versi |
|---|---|
| PHP | 8.2+ |
| Laravel | 11+ |
| Laravel Sanctum | 4+ |
| MySQL / SQLite | — |

---

## ✨ Fitur API

| Fitur | Endpoint |
|---|---|
| Autentikasi (Login/Register/Logout) | `/api/login`, `/api/register`, `/api/logout` |
| Daftar & Detail Event | `GET /api/events`, `GET /api/events/:slug` |
| Denah Kursi per Sesi | `GET /api/sessions/:id/seats` |
| Kunci Kursi | `POST /api/sessions/:id/lock-seat` |
| Checkout | `POST /api/checkout` |
| CRUD Event (Admin) | `/api/admin/events` |
| CRUD Sesi (Admin) | `/api/admin/events/:id/sessions` |
| Daftar Order (Finance) | `GET /api/finance/orders` |
| Verifikasi Pembayaran (Finance) | `POST /api/orders/:code/verify` |
| Scan QR Gate | `POST /api/gate/scan` |

---

## 🗂️ Struktur Penting

```
app/
├── Http/Controllers/Api/
│   ├── AuthController.php      # Login, Register, Logout, Me
│   ├── EventController.php     # Publik & Admin CRUD Event
│   ├── SessionController.php   # Admin CRUD Sesi
│   ├── SeatController.php      # Denah kursi & lock seat
│   ├── OrderController.php     # Checkout, Finance order list, Verify
│   └── GateController.php      # Scan QR gate
├── Models/
│   ├── User.php
│   ├── Event.php
│   ├── EventSession.php
│   ├── Seat.php
│   ├── TicketCategory.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── OrderPayment.php
│   └── Ticket.php
routes/
└── api.php                     # Semua route API
config/
└── cors.php                    # Konfigurasi CORS untuk frontend
database/
└── seeders/DatabaseSeeder.php  # Data dummy lengkap
```

---

## 🚀 Cara Menjalankan

### 1. Clone & Install

```bash
git clone https://github.com/alikmakanmie/laraveltiketin.git
cd laraveltiketin
composer install
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laraveltiketin
DB_USERNAME=root
DB_PASSWORD=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

### 3. Migrasi & Seeder

```bash
php artisan migrate --seed
```

### 4. Jalankan Server

```bash
php artisan serve
```

API akan berjalan di: **http://localhost:8000**

---

## 🔒 Autentikasi

Menggunakan **Laravel Sanctum** dengan token Bearer.

```http
Authorization: Bearer {token}
```

Token diperoleh setelah login/register via `POST /api/login`.

---

## 👥 Role Pengguna

| Role | Akses |
|---|---|
| `buyer` | Lihat event, pilih kursi, checkout |
| `admin` | CRUD event & sesi |
| `finance` | Lihat order, verifikasi pembayaran |
| `gate_officer` | Scan QR tiket masuk |

### Akun Demo (dari seeder)

| Email | Password | Role |
|---|---|---|
| `admin@tiket.in` | `password` | admin |
| `finance@tiket.in` | `password` | finance |
| `gate@tiket.in` | `password` | gate_officer |
| `budi@example.com` | `password` | buyer |

---

## 🌐 CORS

Frontend yang diizinkan sudah dikonfigurasi di `config/cors.php`:

```php
'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
],
```

---

## 📋 API Reference

### Auth

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/api/login` | ❌ | Login, kembalikan token |
| POST | `/api/register` | ❌ | Registrasi user baru |
| POST | `/api/logout` | ✅ | Hapus token sesi |
| GET | `/api/user` | ✅ | Info user yang sedang login |

### Event (Publik)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/events` | Daftar event published |
| GET | `/api/events/:slug` | Detail event + sesi |

### Kursi & Pemesanan

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/api/sessions/:id/seats` | ❌ | Denah kursi sesi |
| POST | `/api/sessions/:id/lock-seat` | ❌ | Kunci kursi sementara |
| POST | `/api/checkout` | ❌ | Buat order |

### Admin — Event & Sesi

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/events` | Semua event |
| POST | `/api/admin/events` | Buat event |
| GET | `/api/admin/events/:id` | Detail event |
| PUT | `/api/admin/events/:id` | Update event |
| DELETE | `/api/admin/events/:id` | Hapus event |
| GET | `/api/admin/events/:id/sessions` | Daftar sesi |
| POST | `/api/admin/events/:id/sessions` | Buat sesi |
| GET | `/api/admin/sessions/:id` | Detail sesi |
| PUT | `/api/admin/sessions/:id` | Update sesi |
| DELETE | `/api/admin/sessions/:id` | Hapus sesi |

### Finance

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/finance/orders` | Daftar order (paginated) |
| POST | `/api/orders/:code/verify` | Verifikasi pembayaran |

### Gate

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/gate/scan` | Scan & validasi QR tiket |

---

## 🔗 Frontend

Repo frontend: [alikmakanmie/ticketing-fe](https://github.com/alikmakanmie/ticketing-fe)

---

## 📄 Lisensi

MIT License © 2026 alikmakanmie
