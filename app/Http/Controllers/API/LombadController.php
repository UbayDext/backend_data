<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ekskul;
use App\Models\Lombad;
use Illuminate\Http\Request;

class LombadController extends Controller
{
    // List semua lomba
public function index(Request $r)
{
    $q = \App\Models\Lombad::query()
        ->with('ekskul') // ✅ ini yang bikin relasi ikut diambil
        ->latest('id');

    $q->when($r->filled('ekskul_id'), fn($x) => $x->where('ekskul_id', (int)$r->ekskul_id));
    $q->when($r->filled('status'),    fn($x) => $x->where('status',$r->status));
    $q->when($r->filled('studi_id'),  fn($x) => $x->whereHas('ekskul', fn($y) => $y->where('studi_id',(int)$r->studi_id)));

    $data = $q->get();

    return response()->json([
        'success' => true,
        'message' => 'Daftar lomba berhasil diambil.',
        'data'    => $data,
    ],200);
}



    // Tambah lomba
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:Individu,Team',
            'ekskul_id' => 'required|exists:ekskuls,id',
        ]);

        $lomba = Lombad::create($data);

        // reload dengan relasi
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil ditambahkan.',
            'data' => $lomba,
        ], 201);
    }

    // Lihat detail lomba
    public function show($id)
    {
        $lomba = Lombad::with('ekskul')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail lomba berhasil diambil.',
            'data' => $lomba,
        ], 200);
    }

    // Update lomba
    public function update(Request $request, $id)
    {
        $lomba = Lombad::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:Individu,Team',
            'ekskul_id' => 'sometimes|required|exists:ekskuls,id',
        ]);

        $lomba->update($data);

        // reload dengan relasi
        $lomba = Lombad::with('ekskul')->find($lomba->id);

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil diperbarui.',
            'data' => $lomba,
        ], 200);
    }

    // Hapus lomba
    public function destroy($id)
    {
        $lomba = Lombad::findOrFail($id);
        $lomba->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil dihapus.'
        ], 200);
    }
}
