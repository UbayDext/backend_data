<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Ekskul;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource (GET /api/students).
     */
    public function index()
    {
        // Dengan eager loading relasi
        $students = Student::with(['classroom', 'ekskul', 'studi'])->get();
        return response()->json($students);
    }

    /**
     * Store a newly created resource in storage (POST /api/students).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'studi_id'     => 'required|exists:studis,id',
            'ekskul_id'    => 'required|exists:ekskuls,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Siswa berhasil ditambahkan',
            'student' => $student
        ], 201);
    }

    /**
     * Display the specified resource (GET /api/students/{id}).
     */
    public function show($id)
    {
        $student = Student::with(['classroom', 'ekskul', 'studi'])->find($id);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        }
        return response()->json($student);
    }

    /**
     * Update the specified resource in storage (PUT/PATCH /api/students/{id}).
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:255',
            'classroom_id' => 'sometimes|required|exists:classrooms,id',
            'studi_id'     => 'sometimes|required|exists:studis,id',
            'ekskul_id'    => 'sometimes|required|exists:ekskuls,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Siswa berhasil diupdate',
            'student' => $student
        ]);
    }

    /**
     * Remove the specified resource from storage (DELETE /api/students/{id}).
     */
    public function destroy($id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        }

        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Siswa berhasil dihapus'
        ]);
    }

    /**
     * Import students from Excel (POST /api/students/import).
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        $data = Excel::toArray([], $request->file('file'))[0];
        $result = [];
        $failed = [];
        $count = 0;

        foreach ($data as $i => $row) {
            if ($i == 0 && (!isset($row[1]) || strtolower($row[0]) == 'nama')) continue;
            if (count($row) < 3 || $count >= 50) continue;

            $name      = trim($row[0] ?? '');
            $classroom = trim($row[1] ?? '');
            $ekskul    = trim($row[2] ?? '');

            $classroomModel = Classroom::where('name', $classroom)->first();
            $ekskulModel    = Ekskul::where('nama_ekskul', $ekskul)->first();
            $studi_id       = $classroomModel ? $classroomModel->studi_id : null;

            if ($name && $classroomModel && $ekskulModel && $studi_id) {
                $result[] = Student::create([
                    'name'         => $name,
                    'classroom_id' => $classroomModel->id,
                    'studi_id'     => $studi_id,
                    'ekskul_id'    => $ekskulModel->id,
                ]);
                $count++;
            } else {
                $failed[] = [
                    'row'       => $i + 1,
                    'name'      => $name,
                    'classroom' => $classroom,
                    'ekskul'    => $ekskul,
                    'error'     => !$classroomModel ? 'Classroom tidak ditemukan' : (!$ekskulModel ? 'Ekskul tidak ditemukan' : 'Data tidak lengkap'),
                ];
            }
        }

        return response()->json([
            'status'   => 'success',
            'message'  => count($result) . ' siswa berhasil diimport',
            'imported' => $result,
            'failed'   => $failed,
        ]);
    }
}
