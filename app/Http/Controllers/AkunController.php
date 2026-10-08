<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AkunController extends Controller
{
    public function form()
    {
        return view('akun.sandi');
    }

    public function simpan(Request $r)
    {
        $d = $r->validate([
            'sandi_lama' => ['required', 'current_password'],
            'sandi_baru' => ['required', 'string', 'min:8', 'confirmed', 'different:sandi_lama'],
        ]);

        $r->user()->update(['password' => $d['sandi_baru']]);

        return redirect()->route('akun.sandi')->with('ok', 'Kata sandi diganti.');
    }
}