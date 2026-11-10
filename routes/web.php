<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SoapController;

// Redirect halaman utama ke halaman login
Route::get('/', function () {
    return redirect()->route('login');
});

// Route Autentikasi (Bisa diakses tanpa login)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route Terproteksi (Hanya bisa diakses jika Pustakawan SUDUH login)
Route::middleware(['auth'])->group(function () {
    Route::resource('buku', BukuController::class);
    Route::resource('kategori', KategoriController::class);

    Route::resource('peminjamans', PeminjamanController::class);
    Route::post('peminjamans/{id}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjamans.kembalikan');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
});

// Route Layanan SOAP (Tugas Minggu 9)
Route::get('/soap/sipus.wsdl', [SoapController::class, 'wsdl']);
Route::post('/soap/sipus', [SoapController::class, 'handle']);