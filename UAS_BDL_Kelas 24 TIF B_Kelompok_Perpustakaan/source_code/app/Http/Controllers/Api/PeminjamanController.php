<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use App\Models\Buku;
use App\Http\Requests\PeminjamanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $query = Peminjaman::with(['user', 'detailPeminjaman.buku']);

        if ($request->user()->role === 'anggota') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json(['data' => $query->get()], 200);
    }

    public function store(PeminjamanRequest $request)
    {
        // Multi-step validation
        $userId = $request->user_id ?? $request->user()->id;

        // 1. Validasi user valid (dilakukan oleh request dan middleware)
        
        try {
            DB::beginTransaction();

            $bukuIds = $request->buku_ids;
            $bukus = Buku::whereIn('id', $bukuIds)->lockForUpdate()->get();

            // 2. Validasi ketersediaan stok
            if ($bukus->count() !== count(array_unique($bukuIds))) {
                DB::rollBack();
                return response()->json(['message' => 'Beberapa buku tidak ditemukan'], 404);
            }

            foreach ($bukus as $buku) {
                if ($buku->stok < 1) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Stok buku habis', 
                        'buku' => $buku->judul
                    ], 422);
                }
            }

            // 3. Validasi belum ada pinjaman aktif untuk buku yang sama oleh user ini
            $activePinjaman = DetailPeminjaman::whereHas('peminjaman', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', 'dipinjam');
            })->whereIn('buku_id', $bukuIds)->where('status_kembali', false)->exists();

            if ($activePinjaman) {
                DB::rollBack();
                return response()->json(['message' => 'User masih meminjam salah satu buku ini dan belum dikembalikan'], 409);
            }

            // Create Peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => $userId,
                'tanggal_pinjam' => now()->toDateString(),
                'tenggat_kembali' => $request->tenggat_kembali,
                'status' => 'dipinjam',
            ]);

            // Create Details and Decrement Stock
            foreach ($bukus as $buku) {
                DetailPeminjaman::create([
                    'peminjaman_id' => $peminjaman->id,
                    'buku_id' => $buku->id,
                    'status_kembali' => false,
                ]);

                $buku->decrement('stok');
            }

            DB::commit();

            return response()->json([
                'message' => 'Peminjaman berhasil',
                'data' => $peminjaman->load('detailPeminjaman.buku')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    public function kembali(Request $request, string $id)
    {
        $request->validate([
            'buku_ids' => 'required|array|min:1',
            'buku_ids.*' => 'required|exists:buku,id',
        ]);

        try {
            DB::beginTransaction();

            $peminjaman = Peminjaman::with('detailPeminjaman')->lockForUpdate()->find($id);

            if (!$peminjaman) {
                DB::rollBack();
                return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
            }

            // Check authorization
            if ($request->user()->role === 'anggota' && $peminjaman->user_id !== $request->user()->id) {
                DB::rollBack();
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $bukuIds = $request->buku_ids;
            $details = $peminjaman->detailPeminjaman()->whereIn('buku_id', $bukuIds)->where('status_kembali', false)->get();

            if ($details->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Buku tidak ditemukan dalam daftar pinjaman ini atau sudah dikembalikan'], 422);
            }

            foreach ($details as $detail) {
                // Update status_kembali
                $detail->update([
                    'status_kembali' => true,
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                // Increment stock
                Buku::where('id', $detail->buku_id)->increment('stok');
            }

            // Check if all books are returned
            $allReturned = $peminjaman->detailPeminjaman()->where('status_kembali', false)->doesntExist();
            if ($allReturned) {
                $peminjaman->update(['status' => 'dikembalikan']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pengembalian buku berhasil',
                'data' => $peminjaman->fresh('detailPeminjaman.buku')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }
}
