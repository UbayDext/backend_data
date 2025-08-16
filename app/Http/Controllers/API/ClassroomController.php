<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $query = Classroom::with('studi')->withCount('students');
    if ($request->has('studi_id')) {
        $query->where('studi_id', $request->studi_id);
    }
    return $query->get();
}


   public function kelasByJenjang($nama_studi)
{
    $kelas = Classroom::with('studi')
        ->withCount('students')
        ->whereHas('studi', function ($q) use ($nama_studi) {
            $q->where('nama_studi', $nama_studi);
        })
        ->get();

    return response()->json($kelas);
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'studi_id' => 'required|exists:studis,id',
        ]);

        $kelas = Classroom::create([
            'name' => $request->name,
            'studi_id' => $request->studi_id,
        ]);

        return response()->json($kelas, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kelas = Classroom::with('studi')->withCount('students')->findOrFail($id);
        return response()->json($kelas);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,string $id)
    {
        $request->validate([
            'name' => 'required|string',
            'studi_id' => 'required|exists:studis,id',
        ]);

        $kelas = Classroom::findOrFail($id);
        $kelas->update([
            'name' => $request->name,
            'studi_id' => $request->studi_id,
        ]);

        return response()->json($kelas);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kelas = Classroom::findOrFail($id);
        $kelas->delete();
        return response()->json(['message' => 'kelas berhasil di hapus']);
    }
}
