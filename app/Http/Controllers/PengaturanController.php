<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tanggalTenggat = (int) DB::table('pengaturan')
            ->where('kunci', 'tanggal_tenggat')
            ->value('nilai') ?? 1;

        return view('pengaturan.index', compact('tanggalTenggat'));
    }

    public function tenggat(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'tanggal' => ['required', 'integer', 'between:1,31'],
        ]);

        DB::table('pengaturan')->updateOrInsert(
            ['kunci' => 'tanggal_tenggat'],
            ['nilai' => $data['tanggal'], 'keterangan' => 'Tanggal tenggat yang tampil di beranda']
        );

        return back()->with('ok', 'Tanggal tenggat diubah menjadi tanggal '.$data['tanggal'].'.');
    }
}