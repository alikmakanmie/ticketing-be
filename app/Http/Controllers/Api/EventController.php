<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Menampilkan daftar semua event yang sedang published (aktif)
     */
    public function index()
    {
        // Ambil event yang statusnya published, urutkan dari yang terbaru
        $events = Event::published()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Event Aktif',
            'data' => $events
        ]);
    }

    /**
     * Menampilkan detail sebuah event beserta sesi-sesinya.
     * Dipanggil saat user masuk ke halaman detail Event "Kancah Seni 2026".
     */
    public function show($slug)
    {
        // Load event berdasarkan slug beserta relasi sesi yang open/upcoming
        $event = Event::published()
            ->where('slug', $slug)
            ->with(['sessions' => function ($query) {
            // Tampilkan hanya sesi yang 'open' atau 'upcoming'
            $query->whereIn('status', ['open', 'upcoming'])
                ->orderBy('event_date', 'asc')
                ->orderBy('start_time', 'asc');
        }])
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak ditemukan atau sudah ditutup',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Event & Sesi',
            'data' => $event
        ]);
    }
}