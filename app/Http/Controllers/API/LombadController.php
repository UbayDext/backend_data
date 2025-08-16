<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lombad;
use Illuminate\Http\Request;

class LombadController extends Controller
{
    // List semua lomba
    public function index(Request $request)
    {
        $query = Lombad::with('ekskul');
        if ($request->has('ekskul_id')) {
            $query->where('ekskul_id', $request->ekskul_id);
        }
        $lombas = $query->get();
        return response()->json($lombas);
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
        return response()->json($lomba, 201);
    }

    // Lihat detail lomba
    public function show($id)
    {
        $lomba = Lombad::with('ekskul')->findOrFail($id);
        return response()->json($lomba);
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
        return response()->json($lomba);
    }

    // Hapus lomba
    public function destroy($id)
    {
        $lomba = LombaD::findOrFail($id);
        $lomba->delete();
        return response()->json(['message' => 'Lomba deleted']);
    }
}

