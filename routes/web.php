<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengaturanController;
use Illuminate\Support\Facades\Route;

// "/" = halaman login untuk tamu, dan beranda (daftar tagihan) setelah login.
Route::get('/', [BerandaController::class, 'index'])->name('beranda');

// Laravel mengarahkan tamu ke route bernama "login"; di sini cukup kembali ke "/".
Route::get('/login', fn () => redirect()->route('beranda'))->name('login');

Route::post('/login', [LoginController::class, 'masuk'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('login.proses');

Route::middleware('auth')->group(function () {
    Route::get('/logout', [LoginController::class, 'keluar'])->name('logout');
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');
    Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::post('/pengaturan/tenggat', [PengaturanController::class, 'tenggat'])->name('pengaturan.tenggat');
});
