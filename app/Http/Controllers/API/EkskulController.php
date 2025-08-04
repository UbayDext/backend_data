<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ekskul;
use Illuminate\Http\Request;

class EkskulController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Ekskul::with('studi')->withCount('students')->get();
    }

    public function EkskulByJenjang($nama_studi)
    {
        $ekskul = Ekskul::with('studi')
        ->withCount('students')
        ->whereHas('studi', function ($q) use ($nama_studi) {
            $q->where('nama_studi', $nama_studi);
        })
        ->get();

        return response()->json($ekskul);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_ekskul' => 'required|string',
            'studi_id' => 'required|exists:studis,id',
        ]);

        $ekskul = Ekskul::create([
            'nama_ekskul' => $request->nama_ekskul,
            'studi_id' => $request->studi_id,
        ]);

        return response()->json($ekskul, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ekskul = Ekskul::with('studi')->withCount('students')->findOrFail($id);
        return response()->json($ekskul);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_ekskul' => 'required|string',
            'studi_id' => 'required|exists:studis,id',
        ]);

        $ekskul = Ekskul::findOrFail($id);
        $ekskul->update([
            'nama_ekskul' => $request->nama_ekskul,
            'studi_id' => $request->studi_id,
        ]);

        return response()->json($ekskul);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ekskul = Ekskul::findOrFail($id);
        $ekskul->delete();
        return response()->json(null, 204);
    }
}
