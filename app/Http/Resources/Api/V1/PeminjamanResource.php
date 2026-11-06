<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $links = [
            'self' => ['href' => url("/api/v1/peminjamans/{$this->id}"), 'method' => 'GET'],
        ];

        // HATEOAS: Link kembalikan HANYA muncul jika status masih 'dipinjam'
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = [
                'href' => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH'
            ];
        }

        return [
            'id' => $this->id,
            'buku_id' => $this->buku_id,
            'nim_peminjam' => $this->nim_peminjam,
            'nama_peminjam' => $this->nama_peminjam,
            'tanggal_pinjam' => $this->created_at?->toIso8601String(),
            'tanggal_kembali' => $this->tanggal_kembali,
            'status' => $this->status,
            'buku' => new BukuResource($this->whenLoaded('buku')),
            '_links' => $links,
        ];
    }
}