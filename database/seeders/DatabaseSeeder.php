<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Seat;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Buat User dengan berbagai Role ────────────────────────────────────

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@tiket.in',
            'password' => Hash::make('password'),
            'phone' => '08111000001',
            'role' => 'admin',
        ]);

        $finance = User::create([
            'name' => 'Fionna Finance',
            'email' => 'finance@tiket.in',
            'password' => Hash::make('password'),
            'phone' => '08111000002',
            'role' => 'finance',
        ]);

        User::create([
            'name' => 'Budi Gate Officer',
            'email' => 'gate@tiket.in',
            'password' => Hash::make('password'),
            'phone' => '08111000003',
            'role' => 'gate_officer',
        ]);

        User::create([
            'name' => 'Andi Pembeli',
            'email' => 'buyer@tiket.in',
            'password' => Hash::make('password'),
            'phone' => '08222000001',
            'role' => 'buyer',
        ]);

        // ─── 2. Buat Event Contoh ─────────────────────────────────────────────────

        $event = Event::create([
            'created_by' => $admin->id,
            'name' => 'Kancah Seni 2026',
            'slug' => 'kancah-seni-2026',
            'description' => 'Festival seni budaya terbesar tahun 2026 menghadirkan penampilan musik, tari, dan pameran seni rupa dari seluruh Nusantara.',
            'venue' => 'Gedung Kesenian Jakarta',
            'venue_address' => 'Jl. Pos No.1, Ps. Baru, Kec. Sawah Besar, Kota Jakarta Pusat',
            'city' => 'Jakarta',
            'status' => 'published',
        ]);

        // ─── 3. Buat 3 Sesi Event ─────────────────────────────────────────────────

        $sessionMalam1 = EventSession::create([
            'event_id' => $event->id,
            'name' => 'Malam 1 - Pembukaan',
            'event_date' => '2026-06-20',
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'total_seats' => 0, // akan di-update setelah generate kursi
            'available_seats' => 0,
            'status' => 'open',
        ]);

        $sessionMalam2 = EventSession::create([
            'event_id' => $event->id,
            'name' => 'Malam 2 - Puncak Acara',
            'event_date' => '2026-06-21',
            'start_time' => '19:00:00',
            'end_time' => '22:30:00',
            'total_seats' => 0,
            'available_seats' => 0,
            'status' => 'open',
        ]);

        $sessionMalam3 = EventSession::create([
            'event_id' => $event->id,
            'name' => 'Malam 3 - Penutupan',
            'event_date' => '2026-06-22',
            'start_time' => '19:30:00',
            'end_time' => '22:00:00',
            'total_seats' => 0,
            'available_seats' => 0,
            'status' => 'open',
        ]);

        // ─── 4. Buat Kategori Tiket + Generate Kursi untuk Malam 1 ───────────────

        $this->generateSeatsForSession($sessionMalam1);
        $this->generateSeatsForSession($sessionMalam2);
        $this->generateSeatsForSession($sessionMalam3);

        // ─── 5. Simulasikan Transaksi Dummy (Beberapa kursi dikunci dan dibeli) ───
        $this->createDummyTransactions($sessionMalam1);
    }

    /**
     * Fungsi untuk generate kursi massal (Bulk Insert) per sesi.
     * Ini adalah simulasi dari API "Generate Denah Kursi" yang dipanggil Admin.
     *
     * Layout:
     *   - Baris V (1-50)  → Kategori VIP     @ Rp 150.000 (50 kursi)
     *   - Baris A-E (1-50) → Kategori Reguler @ Rp  75.000 (250 kursi)
     * Total: 300 kursi per sesi
     */
    private function generateSeatsForSession(EventSession $session): void
    {
        // Buat kategori VIP
        $vip = TicketCategory::create([
            'session_id' => $session->id,
            'name' => 'VIP',
            'color_hex' => '#FFD700',
            'description' => 'Kursi terdepan dengan akses backstage, goodie bag eksklusif',
            'price' => 150000,
            'quota' => 50,
            'available_quota' => 50,
            'is_active' => true,
        ]);

        // Buat kategori Reguler
        $reguler = TicketCategory::create([
            'session_id' => $session->id,
            'name' => 'Reguler',
            'color_hex' => '#4A90E2',
            'description' => 'Kursi belakang dengan pemandangan penuh ke panggung',
            'price' => 75000,
            'quota' => 250,
            'available_quota' => 250,
            'is_active' => true,
        ]);

        $seats = [];

        // Generate Baris V (VIP) - 50 kursi
        for ($i = 1; $i <= 50; $i++) {
            $seats[] = [
                'session_id' => $session->id,
                'category_id' => $vip->id,
                'seat_code' => 'V' . $i,
                'row_label' => 'V',
                'seat_number' => $i,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Generate Baris A-E (Reguler) - 5 baris × 50 kursi = 250 kursi
        $regularRows = ['A', 'B', 'C', 'D', 'E'];
        foreach ($regularRows as $row) {
            for ($i = 1; $i <= 50; $i++) {
                $seats[] = [
                    'session_id' => $session->id,
                    'category_id' => $reguler->id,
                    'seat_code' => $row . $i,
                    'row_label' => $row,
                    'seat_number' => $i,
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk Insert - jauh lebih efisien daripada looping Model::create()
        Seat::insert($seats);

        // Update counter sesi
        $session->update([
            'total_seats' => 300,
            'available_seats' => 300,
        ]);
    }

    /**
     * Membuat data transaksi dummy agar frontend bisa melihat state kursi yang berbeda
     * (ada yang locked, ada yang booked).
     */
    private function createDummyTransactions(EventSession $session)
    {
        $buyer = User::where('email', 'buyer@tiket.in')->first();
        $admin = User::where('email', 'finance@tiket.in')->first();

        // 1. Simulasikan 4 Kursi sedang di-Lock (Pilih kursi, tapi belum bayar)
        $lockedSeats = Seat::where('session_id', $session->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        foreach ($lockedSeats as $seat) {
            $seat->update([
                'status' => 'locked',
                'locked_by' => $buyer->id,
                'locked_until' => now()->addMinutes(12),
            ]);
        }

        // 2. Simulasikan 6 Kursi sudah ter-Booked (Lunas & Punya E-Ticket)
        $bookedSeats = Seat::where('session_id', $session->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        foreach ($bookedSeats as $seat) {
            $category = $seat->category;

            // Buat Order
            $order = \App\Models\Order::create([
                'user_id' => $buyer->id,
                'session_id' => $session->id,
                'order_code' => 'TKT-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'subtotal' => $category->price,
                'service_fee' => 2500,
                'total_amount' => $category->price + 2500,
                'status' => \App\Models\Order::STATUS_PAID,
                'payment_deadline' => now()->subHours(2),
                'paid_at' => now()->subMinutes(30),
                'verified_by' => $admin->id,
            ]);

            // Buat Order Item
            $item = \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'seat_id' => $seat->id,
                'category_id' => $category->id,
                'category_name_snapshot' => $category->name,
                'price_snapshot' => $category->price,
                'seat_code_snapshot' => $seat->seat_code,
            ]);

            // Buat Payment
            \App\Models\OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => 'bank_transfer',
                'status' => \App\Models\OrderPayment::STATUS_VERIFIED,
                'amount_paid' => $order->total_amount,
                'verified_at' => now()->subMinutes(30),
            ]);

            // Terbitkan E-Ticket
            \App\Models\Ticket::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'user_id' => $buyer->id,
                'ticket_code' => 'QR-DUMMY-' . strtoupper(Str::random(10)),
                'status' => \App\Models\Ticket::STATUS_ISSUED,
                'event_name_snapshot' => $session->event->name,
                'session_name_snapshot' => $session->name,
                'event_date_snapshot' => $session->event_date,
                'start_time_snapshot' => $session->start_time,
                'venue_snapshot' => $session->event->venue,
                'seat_code_snapshot' => $seat->seat_code,
                'category_name_snapshot' => $category->name,
                'price_paid_snapshot' => $category->price,
            ]);

            // Update status kursi
            $seat->update(['status' => 'booked']);

            // Kurangi sisa kursi pada sesi
            $session->decrement('available_seats');
            $category->decrement('available_quota');
        }
    }
}