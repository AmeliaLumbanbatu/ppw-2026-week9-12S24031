<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BukuApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);

        $bukus = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($request->query('search'), function ($q, $s) {
                $q->where('judul', 'like', "%{$s}%")
                  ->orWhere('penulis', 'like', "%{$s}%")
                  ->orWhere('isbn', 'like', "%{$s}%");
            })
            ->when($request->query('kategori_id'), fn ($q, $k) => $q->where('kategori_id', $k))
            ->when($request->boolean('tersedia'), fn ($q) => $q->where('stok', '>', 0))
            ->paginate($perPage);

        return $this->successResponse(
            BukuResource::collection($bukus->items()),
            'Daftar katalog buku berhasil diambil.',
            200,
            [
                'current_page' => $bukus->currentPage(),
                'total_items' => $bukus->total(),
                'total_pages' => $bukus->lastPage(),
            ]
        );
    }

    public function show(string $id): JsonResponse
    {
        $buku = Buku::with('kategori')->find($id);
        if (!$buku) return $this->problemResponse('Not Found', 'Buku tidak ditemukan.', 404, 'buku-not-found');
        return $this->successResponse(new BukuResource($buku), 'Detail buku ditemukan.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'judul' => 'required|string|max:255',
            'penulis' => 'required|string|max:150',
            'isbn' => 'required|string|unique:bukus,isbn',
            'stok' => 'required|integer|min:0',
        ]);

        $buku = Buku::create($validated);
        return $this->createdResponse(new BukuResource($buku), url("/api/v1/bukus/{$buku->id}"), 'Buku berhasil dibuat.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);
        if (!$buku) return $this->problemResponse('Not Found', 'Buku tidak ditemukan.', 404, 'buku-not-found');

        $isPut = $request->isMethod('put');

        if ($isPut) {
            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategoris,id',
                'judul' => 'required|string|max:255',
                'penulis' => 'required|string|max:150',
                'isbn' => "required|string|unique:bukus,isbn,{$buku->id}",
                'stok' => 'required|integer|min:0',
            ]);
        } else {
            $validated = $request->validate([
                'kategori_id' => 'sometimes|required|exists:kategoris,id',
                'judul' => 'sometimes|required|string|max:255',
                'penulis' => 'sometimes|required|string|max:150',
                'isbn' => "sometimes|required|string|unique:bukus,isbn,{$buku->id}",
                'stok' => 'sometimes|required|integer|min:0',
            ]);
        }

        $buku->update($validated);
        return $this->successResponse(new BukuResource($buku), $isPut ? 'Data buku diganti penuh (PUT).' : 'Data buku diperbarui sebagian (PATCH).');
    }

    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::withCount('peminjamans')->find($id);
        if (!$buku) return $this->problemResponse('Not Found', 'Buku tidak ditemukan.', 404, 'buku-not-found');

        // Tolak jika sedang dipinjam (409 Conflict)
        $activeLoans = $buku->peminjamans()->where('status', 'dipinjam')->count();
        if ($activeLoans > 0) {
            return $this->problemResponse('Conflict', 'Buku tidak dapat dihapus karena sedang dipinjam.', 409, 'buku-has-loans');
        }

        $buku->delete();
        return $this->noContentResponse();
    }

    // FITUR REST CONSUMER (Memanggil OpenLibrary API Eksternal)
    public function referensiEksternal(string $id): JsonResponse
    {
        $buku = Buku::find($id);
        if (!$buku) return $this->problemResponse('Not Found', 'Buku tidak ditemukan.', 404);

        $response = Http::timeout(8)->get("https://openlibrary.org/api/books?bibkeys=ISBN:{$buku->isbn}&format=json&jscmd=data");

        if ($response->failed()) {
            return $this->problemResponse('Bad Gateway', 'Gagal terhubung ke API eksternal OpenLibrary.', 502);
        }

        return $this->successResponse([
            'buku_lokal' => $buku->judul,
            'isbn' => $buku->isbn,
            'metadata_eksternal' => $response->json()["ISBN:{$buku->isbn}"] ?? 'Data metadata tidak ditemukan di OpenLibrary.'
        ], 'Metadata eksternal berhasil diambil.');
    }
}