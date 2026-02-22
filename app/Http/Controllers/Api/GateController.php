<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScanLog;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GateController extends Controller
{
    /**
     * FASE 4: Hari H Acara (Check-In Gate / Validasi QR)
     * Admin/Panitia menscan tiket pengunjung dengan Aplikasi/Web Scanner.
     */
    public function scan(Request $request)
    {
        // 1. Validasi Input (Hanya User Gate/Admin yg punya scanner)
        $request->validate([
            'qr_code' => 'required|string', // Isi raw dar scanner
            'device_id' => 'nullable|string', // Agent alat (browser HP dsb)
        ]);

        $officer = auth()->user();

        // Cek Role Scanner (Gate Officer / Admin / Panitia)
        if (!$officer->isGateOfficer() && !$officer->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses sebagai Gate Officer.',
            ], 403);
        }

        $qrData = $request->qr_code;

        // Cari Tiket berdasarkan Kode unik di QR
        $ticket = Ticket::where('ticket_code', $qrData)->first();

        // Skenario 1: TIKET TIDAK DITEMUKAN / PALSU
        if (!$ticket) {
            // Rekam Fraud Log
            ScanLog::create([
                'ticket_code_scanned' => $qrData,
                'ticket_id' => null, // Null krn palsu
                'scanned_by' => $officer->id,
                'result' => ScanLog::RESULT_INVALID_CODE,
                'device_info' => $request->device_id ?? substr($request->userAgent(), 0, 255),
            ]);

            return response()->json([
                'success' => false,
                'alert' => 'danger', // Merah
                'message' => 'Akses Ditolak! QR Code tidak dikenali atau Palsu.',
            ], 404);
        }

        // Skenario 2: TIKET SUDAH DIPAKAI (Calo/Screen capture dibagikan)
        if ($ticket->isAlreadyUsed()) {
            // Rekam Log orang mencoba masuk dgn tiket yg sdh ter-scan sebelumnya
            ScanLog::create([
                'ticket_code_scanned' => $qrData,
                'ticket_id' => $ticket->id,
                'scanned_by' => $officer->id,
                'result' => ScanLog::RESULT_ALREADY_USED,
                'device_info' => $request->device_id ?? substr($request->userAgent(), 0, 255),
            ]);

            return response()->json([
                'success' => false,
                'alert' => 'danger', // Merah
                'message' => 'Tiket Sudah Digunakan! (Gate: ' . $ticket->scannedByOfficer->name . ' pd ' . $ticket->used_at->format('H:i') . ')',
            ], 409); // Conflict
        }

        // Skenario 3: TIKET DIBATALKAN OLEH SISTEM (Refund, Void, dsb)
        if ($ticket->isVoided()) {
            ScanLog::create([
                'ticket_code_scanned' => $qrData,
                'ticket_id' => $ticket->id,
                'scanned_by' => $officer->id,
                'result' => ScanLog::RESULT_VOIDED,
                'device_info' => $request->device_id,
            ]);

            return response()->json([
                'success' => false,
                'alert' => 'warning', // Kuning/Merah
                'message' => 'Tiket Tidak Berlaku. Tiket ini telah dibatalkan (Void).',
            ], 403);
        }

        // Skenario 4: BERHASIL! VALID.
        // Update State tiket -> USED
        $ticket->status = Ticket::STATUS_USED;
        $ticket->used_at = Carbon::now();
        $ticket->scanned_by = $officer->id;
        $ticket->save();

        // Rekam Histori Sukses
        ScanLog::create([
            'ticket_code_scanned' => $qrData,
            'ticket_id' => $ticket->id,
            'scanned_by' => $officer->id,
            'result' => ScanLog::RESULT_SUCCESS,
            'device_info' => $request->device_id,
        ]);

        return response()->json([
            'success' => true,
            'alert' => 'success', // Hijau
            'message' => 'Akses Diterima!',
            'data' => [
                'event' => $ticket->event_name_snapshot,
                'session' => $ticket->session_name_snapshot,
                'time' => Carbon::parse($ticket->start_time_snapshot)->format('H:i'),
                'category' => $ticket->category_name_snapshot,
                'seat' => $ticket->seat_code_snapshot,
            ]
        ]);
    }
}