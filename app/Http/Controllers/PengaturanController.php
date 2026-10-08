<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
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
