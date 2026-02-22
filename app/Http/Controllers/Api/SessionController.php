<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * GET /api/admin/events/:eventId/sessions
     * List semua sesi milik sebuah event
     */
    public function index($eventId)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $event = Event::findOrFail($eventId);
        $sessions = $event->sessions()->orderBy('event_date')->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data'    => ['event' => $event, 'sessions' => $sessions],
        ]);
    }

    /**
     * POST /api/admin/events/:eventId/sessions
     * Buat sesi baru
     */
    public function store(Request $request, $eventId)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        Event::findOrFail($eventId); // pastikan event ada

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'status'     => 'nullable|in:upcoming,open,sold_out,ongoing,ended',
        ]);

        $data['event_id']        = $eventId;
        $data['status']          = $data['status'] ?? EventSession::STATUS_UPCOMING;
        $data['total_seats']     = 0;
        $data['available_seats'] = 0;

        $session = EventSession::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil dibuat.',
            'data'    => $session,
        ], 201);
    }

    /**
     * GET /api/admin/sessions/:sessionId
     * Detail satu sesi (untuk form edit)
     */
    public function show($sessionId)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $session = EventSession::with('event')->findOrFail($sessionId);

        return response()->json(['success' => true, 'data' => $session]);
    }

    /**
     * PUT /api/admin/sessions/:sessionId
     * Update sesi
     */
    public function update(Request $request, $sessionId)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $session = EventSession::findOrFail($sessionId);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'event_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time'   => 'sometimes|date_format:H:i',
            'status'     => 'sometimes|in:upcoming,open,sold_out,ongoing,ended',
        ]);

        $session->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil diperbarui.',
            'data'    => $session,
        ]);
    }

    /**
     * DELETE /api/admin/sessions/:sessionId
     * Hapus sesi
     */
    public function destroy($sessionId)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $session = EventSession::findOrFail($sessionId);
        $session->delete();

        return response()->json(['success' => true, 'message' => 'Sesi berhasil dihapus.']);
    }
}