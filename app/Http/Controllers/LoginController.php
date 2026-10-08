<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function masuk(Request $request)
    {
        $kredensial = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // "ingat saya" -> Laravel menyimpan remember_token dan cookie login jangka panjang.
        $ingat = $request->boolean('ingat');

        // Hanya akun aktif (is_aktif = 1) yang boleh masuk.
        if (Auth::attempt($kredensial + ['is_aktif' => 1], $ingat)) {
            $request->session()->regenerate();

            return redirect()->intended(route('beranda'));
        }

        return back()
            ->withErrors(['email' => 'Email atau kata sandi salah, atau akun tidak aktif.'])
            ->onlyInput('email', 'ingat');
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }
}
