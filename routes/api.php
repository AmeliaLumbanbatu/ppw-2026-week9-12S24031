<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Endpoint Auth Login (REST API)
Route::post('/v1/auth/login', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Login berhasil',
        'access_token' => '1|laravel_sanctum_token_dummy_12S24031_week9',
        'token_type' => 'Bearer'
    ], 200);
});

// 2. Endpoint Katalog Buku (Public REST API)
Route::get('/v1/bukus', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'data' => [
            [
                'id' => 1,
                'judul' => 'Arsitektur Web Modern',
                'penulis' => 'Chandro Pardede',
                'stok' => 5
            ]
        ],
        '_links' => [
            'self' => url('/api/v1/bukus'),
            'create' => url('/api/v1/bukus')
        ]
    ], 200);
});

// 3. Endpoint Tambah Buku (Private REST API)
Route::post('/v1/bukus', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Buku berhasil ditambahkan via REST API',
        'data' => $request->all()
    ], 201);
});