<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $peminjamans = Peminjaman::with('buku')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('nim'), fn ($q, $n) => $q->where('nim_peminjam', $n))
            ->paginate(10);

        return $this->successResponse(PeminjamanResource::collection($peminjamans), 'Daftar transaksi peminjaman.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'nim_peminjam' => 'required|string|max:15',
            'nama_peminjam' => 'required|string|max:150',
        ]);

        return DB::transaction(function () use ($validated) {
            // Pessimistic Locking untuk mencegah race condition kuota stok
            $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->first();

            if ($buku->stok < 1) {
                return $this->problemResponse('Unprocessable Content', 'Stok buku sedang habis.', 422, 'stok-habis');
            }

            $alreadyBorrowed = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $validated['nim_peminjam'])
                ->where('status', 'dipinjam')
                ->exists();

            if ($alreadyBorrowed) {
                return $this->problemResponse('Conflict', 'Mahasiswa masih meminjam buku yang sama.', 409, 'duplicate-loan');
            }

            $buku->decrement('stok');

            $peminjaman = Peminjaman::create([
                'buku_id' => $buku->id,
                'nim_peminjam' => strtoupper(trim($validated['nim_peminjam'])),
                'nama_peminjam' => $validated['nama_peminjam'],
                'status' => 'dipinjam',
            ]);

            return $this->createdResponse(new PeminjamanResource($peminjaman), url("/api/v1/peminjamans/{$peminjaman->id}"), 'Peminjaman berhasil dicatat.');
        });
    }

    public function kembalikan(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::find($id);

            if (!$peminjaman) return $this->problemResponse('Not Found', 'Data peminjaman tidak ditemukan.', 404);

            if ($peminjaman->status === 'dikembalikan') {
                return $this->problemResponse('Conflict', 'Buku ini sudah dikembalikan sebelumnya.', 409, 'already-returned');
            }

            $buku = Buku::where('id', $peminjaman->buku_id)->lockForUpdate()->first();
            
            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali' => now(),
            ]);

            if ($buku) {
                $buku->increment('stok');
            }

            return $this->successResponse(new PeminjamanResource($peminjaman), 'Buku berhasil dikembalikan.');
        });
    }
}