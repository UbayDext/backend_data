<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lombad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LombadController extends Controller
{
    /**
     * GET /api/lombads
     * Query (opsional): ekskul_id, status (Individu|Team), studi_id (via relasi ekskul.studi_id)
     * Tambah ?debug=1 untuk keluarkan info diagnostik.
     */
   public function index(Request $r)
{
    $q = \App\Models\Lombad::withoutGlobalScopes() // <— matikan scope
        ->with('ekskul')
        ->latest('id');

    $q->when($r->filled('ekskul_id'), fn($x)=>$x->where('ekskul_id',(int)$r->ekskul_id));
    $q->when($r->filled('status'),    fn($x)=>$x->where('status',$r->status));
    $q->when($r->filled('studi_id'),  fn($x)=>$x->whereHas('ekskul',fn($y)=>$y->where('studi_id',(int)$r->studi_id)));

    // kalau hosting pakai read/write split
    $data = $q->useWritePdo()->get();

    return response()->json([
        'success'=>true,
        'message'=>'Daftar lomba berhasil diambil.',
        'data'=>$data,
    ],200);
}

    /**
     * POST /api/lombads
     * Body: name, status (Individu|Team), ekskul_id (exists: ekskuls.id)
     */
    public function store(Request $r)
    {
        $validated = $r->validate([
            'name'      => ['required', 'string', 'max:255'],
            'status'    => ['required', 'in:Individu,Team'],
            'ekskul_id' => ['required', 'exists:ekskuls,id'],
        ]);

        $lomba = Lombad::create($validated);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil ditambahkan.',
            'data'    => $lomba,
        ], 201);
    }

    /**
     * GET /api/lombads/{id}
     */
    public function show($id)
    {
        $lomba = Lombad::with('ekskul')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail lomba berhasil diambil.',
            'data'    => $lomba,
        ], 200);
    }

    /**
     * PUT/PATCH /api/lombads/{id}
     */
    public function update(Request $r, $id)
    {
        $lomba = Lombad::findOrFail($id);

        $validated = $r->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'status'    => ['sometimes', 'required', 'in:Individu,Team'],
            'ekskul_id' => ['sometimes', 'required', 'exists:ekskuls,id'],
        ]);

        $lomba->update($validated);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil diperbarui.',
            'data'    => $lomba,
        ], 200);
    }

    /**
     * DELETE /api/lombads/{id}
     */
    public function destroy($id)
    {
        $lomba = Lombad::findOrFail($id);
        $lomba->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil dihapus.',
        ], 200);
    }
}
