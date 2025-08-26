<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ekskul;
use Illuminate\Http\Request;

class EkskulController extends Controller
{
    /**
     * Display a listing of the resource.
     */public function index(Request $request)
{
    $query = Ekskul::with('studi')->withCount('students');
    if ($request->has('studi_id')) {
        $query->where('studi_id', $request->studi_id);
    }
    $ekskuls = $query->get();

    return response()->json([
        'success' => true,
        'message' => 'Daftar ekskul berhasil diambil.',
        'data'    => $ekskuls,
    ], 200);
}

public function EkskulByJenjang($nama_studi)
{
    $ekskul = Ekskul::with('studi')
        ->withCount('students')
        ->whereHas('studi', function ($q) use ($nama_studi) {
            $q->where('nama_studi', $nama_studi);
        })
        ->get();

    return response()->json([
        'success' => true,
        'message' => "Daftar ekskul untuk jenjang $nama_studi berhasil diambil.",
        'data'    => $ekskul,
    ], 200);
}

public function store(Request $request)
{
    $request->validate([
        'nama_ekskul' => 'required|string',
        'studi_id'    => 'required|exists:studis,id',
    ]);

    $ekskul = Ekskul::create([
        'nama_ekskul' => $request->nama_ekskul,
        'studi_id'    => $request->studi_id,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Ekskul berhasil ditambahkan.',
        'data'    => $ekskul,
    ], 201);
}

public function show($id)
{
    $ekskul = Ekskul::with('studi')->withCount('students')->findOrFail($id);

    return response()->json([
        'success' => true,
        'message' => 'Detail ekskul berhasil diambil.',
        'data'    => $ekskul,
    ], 200);
}

public function update(Request $request, string $id)
{
    $request->validate([
        'nama_ekskul' => 'required|string',
        'studi_id'    => 'required|exists:studis,id',
    ]);

    $ekskul = Ekskul::findOrFail($id);
    $ekskul->update([
        'nama_ekskul' => $request->nama_ekskul,
        'studi_id'    => $request->studi_id,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Ekskul berhasil diperbarui.',
        'data'    => $ekskul,
    ], 200);
}

public function destroy(string $id)
{
    $ekskul = Ekskul::findOrFail($id);
    $ekskul->delete();

    return response()->json([
        'success' => true,
        'message' => 'Ekskul berhasil dihapus.'
    ], 200);
}

}
