<?php



namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SertifikationResource;
use App\Models\Sertifikation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SertifikationController extends Controller
{
    // GET /api/sertifikations
    public function index()
    {
        return SertifikationResource::collection(Sertifikation::all());
    }

    // POST /api/sertifikations
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'student_id'    => 'required|exists:students,id',
            'studi_id'      => 'nullable|exists:studis,id',
            'ekskul_id'     => 'nullable|exists:ekskuls,id',
            'classroom_id'  => 'nullable|exists:classrooms,id',
            'file'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5048',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('sertifikats', 'public');
        }

        $sertifikat = Sertifikation::create([
            'title'         => $validated['title'],
            'student_id'    => $validated['student_id'],
            'studi_id'      => $validated['studi_id'] ?? null,
            'ekskul_id'     => $validated['ekskul_id'] ?? null,
            'classroom_id'  => $validated['classroom_id'] ?? null,
            'file_path'     => $filePath,
        ]);

        return new SertifikationResource($sertifikat);
    }

    // GET /api/sertifikations/{id}
    public function show($id)
    {
        $sertifikat = Sertifikation::findOrFail($id);
        return new SertifikationResource($sertifikat);
    }

    // PUT/PATCH /api/sertifikations/{id}
    public function update(Request $request, $id)
    {
        $sertifikat = Sertifikation::findOrFail($id);

        $validated = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'student_id'    => 'sometimes|required|exists:students,id',
            'studi_id'      => 'nullable|exists:studis,id',
            'ekskul_id'     => 'nullable|exists:ekskuls,id',
            'classroom_id'  => 'nullable|exists:classrooms,id',
            'file'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('file')) {

            if ($sertifikat->file_path && Storage::disk('public')->exists($sertifikat->file_path)) {
                Storage::disk('public')->delete($sertifikat->file_path);
            }
            $filePath = $request->file('file')->store('sertifikats', 'public');
            $sertifikat->file_path = $filePath;
        }

        $sertifikat->update($validated);

        return new SertifikationResource($sertifikat);
    }

    // DELETE /api/sertifikations/{id}
    public function destroy($id)
    {
        $sertifikat = Sertifikation::findOrFail($id);


        if ($sertifikat->file_path && Storage::disk('public')->exists($sertifikat->file_path)) {
            Storage::disk('public')->delete($sertifikat->file_path);
        }

        $sertifikat->delete();

        return response()->json(['message' => 'deleted']);
    }
}
