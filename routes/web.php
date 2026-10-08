<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\KoreksiPembayaranController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NasabahController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenagihController;
use App\Http\Controllers\TagihanController;
use App\Http\Middleware\PastikanAdmin;
use App\Http\Middleware\PastikanAktif;
use Illuminate\Support\Facades\Route;

// "/" = halaman login untuk tamu, dan beranda (daftar tagihan) setelah login.
Route::get('/', [BerandaController::class, 'index'])->name('beranda');

// Laravel mengarahkan tamu ke route bernama "login"; di sini cukup kembali ke "/".
Route::get('/login', fn () => redirect()->route('beranda'))->name('login');

Route::post('/login', [LoginController::class, 'masuk'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('login.proses');

Route::middleware(['auth', PastikanAktif::class])->group(function () {

    // ===== Admin dan penagih =====
    Route::get('/logout', [LoginController::class, 'keluar'])->name('logout');
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');
    Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');

    Route::get('/akun/sandi', [AkunController::class, 'form'])->name('akun.sandi');
    Route::put('/akun/sandi', [AkunController::class, 'simpan'])->name('akun.sandi.simpan');

    // ===== Khusus admin (didaftarkan lebih dulu agar /nasabah/create tidak tertangkap {nasabah}) =====
    Route::middleware(PastikanAdmin::class)->group(function () {
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::post('/pengaturan/tenggat', [PengaturanController::class, 'tenggat'])->name('pengaturan.tenggat');
        Route::patch('/penagih/{penagih}/aktif', [PenagihController::class, 'aktif'])->name('penagih.aktif');
        Route::resource('penagih', PenagihController::class)->except(['show'])
            ->parameters(['penagih' => 'penagih']);

        Route::resource('nasabah', NasabahController::class)->except(['index', 'show'])
            ->parameters(['nasabah' => 'nasabah']);

        Route::resource('tagihan', TagihanController::class)->except(['index', 'show'])
            ->parameters(['tagihan' => 'tagihan']);

        Route::get('/pembayaran/{pembayaran}/koreksi', [KoreksiPembayaranController::class, 'edit'])->name('pembayaran.edit');
        Route::put('/pembayaran/{pembayaran}', [KoreksiPembayaranController::class, 'update'])->name('pembayaran.update');
        Route::delete('/pembayaran/{pembayaran}', [KoreksiPembayaranController::class, 'destroy'])->name('pembayaran.destroy');
    });

    // ===== Baca saja (admin dan penagih) =====
    Route::get('/nasabah', [NasabahController::class, 'index'])->name('nasabah.index');
    Route::get('/nasabah/{nasabah}', [NasabahController::class, 'show'])->name('nasabah.show');
});