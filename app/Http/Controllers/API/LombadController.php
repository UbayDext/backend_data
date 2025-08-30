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
    // Paling minimal: Eloquent murni
    $data = \App\Models\Lombad::select('id','name','status','ekskul_id')->get();

    return response()->json([
        'success' => true,
        'message' => 'Daftar lomba (smoke test).',
        'data'    => $data,
    ], 200)->header('Cache-Control','no-store');
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
