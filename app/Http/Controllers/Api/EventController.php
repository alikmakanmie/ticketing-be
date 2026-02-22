<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    // ─── PUBLIC ────────────────────────────────────────────────────────────────

    /**
     * GET /api/events  — Daftar event published (untuk pembeli)
     */
    public function index()
    {
        $events = Event::published()->with('sessions')->latest()->get();
        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * GET /api/events/:slug  — Detail event + sesi
     */
    public function show($slug)
    {
        $event = Event::published()
            ->where('slug', $slug)
            ->with(['sessions' => fn($q) => $q->whereIn('status', ['open', 'upcoming'])->orderBy('event_date')])
            ->first();

        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $event]);
    }

    // ─── ADMIN ONLY ────────────────────────────────────────────────────────────

    /**
     * GET /api/admin/events  — Semua event (semua status)
     */
    public function adminIndex()
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }
        $events = Event::with('sessions')->latest()->get();
        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * POST /api/admin/events  — Buat event baru
     */
    public function store(Request $request)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'venue'         => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'city'          => 'nullable|string|max:100',
            'status'        => 'nullable|in:draft,published,ended,cancelled',
        ]);

        $data['slug']       = Str::slug($data['name']) . '-' . Str::random(4);
        $data['created_by'] = auth()->id();
        $data['status']     = $data['status'] ?? Event::STATUS_DRAFT;

        $event = Event::create($data);

        return response()->json(['success' => true, 'message' => 'Event berhasil dibuat.', 'data' => $event], 201);
    }

    /**
     * GET /api/admin/events/:id  — Detail event untuk admin (by ID)
     */
    public function adminShow($id)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $event = Event::withTrashed()->with('sessions')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $event]);
    }

    /**
     * PUT /api/admin/events/:id  — Update event
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $event = Event::findOrFail($id);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'venue'         => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'city'          => 'nullable|string|max:100',
            'status'        => 'nullable|in:draft,published,ended,cancelled',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . substr($event->slug, -4);
        }

        $event->update($data);

        return response()->json(['success' => true, 'message' => 'Event berhasil diperbarui.', 'data' => $event]);
    }

    /**
     * DELETE /api/admin/events/:id  — Soft delete event
     */
    public function destroy($id)
    {
        if (!auth()->user()?->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['success' => true, 'message' => 'Event berhasil dihapus.']);
    }
}