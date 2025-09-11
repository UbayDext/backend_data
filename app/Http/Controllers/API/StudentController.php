<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Ekskul;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class StudentController extends Controller
{
    /**
     * GET /api/students
     * Query: classroom_id, ekskul_id, studi_id, search
     */
    public function index(Request $request)
    {
        $q = Student::with(['classroom','ekskul','studi']);

        if ($request->filled('classroom_id')) {
            $q->where('classroom_id', $request->integer('classroom_id'));
        }
        if ($request->filled('ekskul_id')) {
            $q->where('ekskul_id', $request->integer('ekskul_id'));
        }
        if ($request->filled('studi_id')) {
            $q->where('studi_id', $request->integer('studi_id'));
        }
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa berhasil diambil',
            'data'    => $q->get()
        ]);
    }

    /**
     * POST /api/students
     */
  public function store(Request $request)
{
    // Validasi longgar dulu: izinkan salah satu (id ATAU nama)
    $validator = Validator::make($request->all(), [
        'name'            => 'required|string|max:255',
        'classroom_id'    => 'nullable|exists:classrooms,id',
        'classroom_name'  => 'nullable|string',
        'studi_id'        => 'nullable|exists:studis,id',
        'ekskul_id'       => 'nullable|exists:ekskuls,id',
        'ekskul_name'     => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success'=>false,'errors'=>$validator->errors()], 422);
    }

    $data = $validator->validated();

    // --- Classroom: id atau name ---
    if (empty($data['classroom_id'])) {
        $classroom = Classroom::whereRaw('LOWER(name)=?', [strtolower($request->input('classroom_name'))])->first();
        if (!$classroom) {
            return response()->json(['success'=>false,'message'=>'Classroom tidak valid'], 422);
        }
        $data['classroom_id'] = $classroom->id;
        // turunkan studi_id dari classroom kalau tidak dikirim
        $data['studi_id'] = $data['studi_id'] ?? $classroom->studi_id;
    }

    // --- Ekskul: id atau name ---
    if (empty($data['ekskul_id'])) {
        $ekskul = Ekskul::whereRaw('LOWER(nama_ekskul)=?', [strtolower($request->input('ekskul_name'))])->first();
        if (!$ekskul) {
            return response()->json(['success'=>false,'message'=>'Ekskul tidak valid'], 422);
        }
        $data['ekskul_id'] = $ekskul->id;
    }

    // Pastikan studi_id terisi
    if (empty($data['studi_id'])) {
        return response()->json(['success'=>false,'message'=>'studi_id wajib ada (atau ikut dari classroom)'], 422);
    }

    $student = Student::create([
        'name'         => $data['name'],
        'classroom_id' => $data['classroom_id'],
        'studi_id'     => $data['studi_id'],
        'ekskul_id'    => $data['ekskul_id'],
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Siswa berhasil ditambahkan',
        'data'    => $student->load(['classroom','ekskul','studi']),
    ], 201);
}


    /**
     * GET /api/students/{id}
     */
    public function show($id)
    {
        $student = Student::with(['classroom','ekskul','studi'])->find($id);
        if (!$student) {
            return response()->json(['success'=>false,'message' => 'Siswa tidak ditemukan'], 404);
        }
        return response()->json([
            'success'=>true,
            'message'=>'Detail siswa berhasil diambil',
            'data'=>$student,
        ]);
    }

    /**
     * PUT/PATCH /api/students/{id}
     * FE bisa kirim: name, classroom_id+ekskul_id+studi_id atau classroom_name+ekskul_name
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['success'=>false,'message'=>'Siswa tidak ditemukan'], 404);
        }

        // Jalur langsung pakai ID
        if ($request->filled('classroom_id') && $request->filled('ekskul_id') && $request->filled('studi_id')) {
            $data = $request->validate([
                'name'         => 'required|string|max:255',
                'classroom_id' => 'required|exists:classrooms,id',
                'studi_id'     => 'required|exists:studis,id',
                'ekskul_id'    => 'required|exists:ekskuls,id',
            ]);
            $student->update($data);
        }
        else {
            // Fallback: FE kirim nama kelas & ekskul
            $classroom = Classroom::whereRaw('LOWER(name) = ?', [strtolower($request->input('classroom_name'))])->first();
            $ekskul    = Ekskul::whereRaw('LOWER(nama_ekskul) = ?', [strtolower($request->input('ekskul_name'))])->first();

            if (!$classroom || !$ekskul) {
                return response()->json(['success'=>false,'message'=>'Classroom/Ekskul tidak valid'], 422);
            }

            $data = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $student->update([
                'name'         => $data['name'],
                'classroom_id' => $classroom->id,
                'ekskul_id'    => $ekskul->id,
                'studi_id'     => $classroom->studi_id,
            ]);
        }

        return response()->json([
            'success'=>true,
            'message'=>'Siswa berhasil diupdate',
            'data'=>$student->load(['classroom','ekskul','studi'])
        ]);
    }

    /**
     * DELETE /api/students/{id}
     */
    public function destroy($id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['success'=>false,'message'=>'Siswa tidak ditemukan'], 404);
        }

        DB::transaction(function () use ($student) {
            $student->raceParticipants()->delete();
            $student->delete();
        });

        return response()->json(['success'=>true,'message'=>'Siswa berhasil dihapus']);
    }

    /**
     * POST /api/students/import_many
     */
   public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB
        ]);

        try {
            $sheets = Excel::toArray([], $request->file('file'));
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membaca file: '.$e->getMessage(),
            ], 422);
        }

        if (empty($sheets) || empty($sheets[0])) {
            return response()->json(['status'=>'error','message'=>'Sheet kosong / format tidak dikenali'], 422);
        }

        $data        = $sheets[0];
        $studiFilter = $request->integer('studi_id'); // optional
        $result = [];
        $failed = [];
        $count  = 0;

        foreach ($data as $i => $row) {
            // lewati header jika kolom pertama 'nama'
            if ($i == 0 && isset($row[0]) && strtolower(trim($row[0])) === 'nama') continue;

            if (count($row) < 3 || $count >= 200) continue; // batasi 200 baris per import (ubah sesuai kebutuhan)

            $name      = trim(($row[0] ?? ''));
            $classroom = trim(($row[1] ?? ''));
            $ekskul    = trim(($row[2] ?? ''));

            if ($name === '' || $classroom === '' || $ekskul === '') {
                $failed[] = ['row' => $i + 1, 'error' => 'Data tidak lengkap'];
                continue;
            }

            $classroomQuery = Classroom::whereRaw('LOWER(name) = ?', [strtolower($classroom)]);
            if ($studiFilter) {
                $classroomQuery->where('studi_id', $studiFilter);
            }
            $classroomModel = $classroomQuery->first();

            $ekskulModel = Ekskul::whereRaw('LOWER(nama_ekskul) = ?', [strtolower($ekskul)])->first();
            $studi_id    = $classroomModel?->studi_id;

            if ($classroomModel && $ekskulModel && $studi_id) {
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
                    'error'     => !$classroomModel ? 'Classroom tidak ditemukan'
                                   : (!$ekskulModel ? 'Ekskul tidak ditemukan' : 'Studi ID tidak valid'),
                ];
            }
        }

        return response()->json([
            'status'   => 'success',
            'message'  => count($result) . ' siswa berhasil diimport',
            'imported' => collect($result)->map(fn($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'classroom_id' => $s->classroom_id,
                'studi_id'     => $s->studi_id,
                'ekskul_id'    => $s->ekskul_id,
            ]),
            'failed'   => $failed,
        ]);
    }

    /**
     * POST /api/students/import
     */
public function importMany(Request $request)
{
    try {
        $validated = $request->validate([
            'students' => ['required','array','min:1'],
            'students.*.name'         => ['required','string','max:255'],
            'students.*.classroom_id' => ['required','integer','exists:classrooms,id'],
            'students.*.studi_id'     => ['required','integer','exists:studis,id'],
            'students.*.ekskul_id'    => ['nullable','integer','exists:ekskuls,id'],
        ]);

        $imported = [];
        $failed = [];

        foreach ($validated['students'] as $row) {
            try {
                $student = Student::create([
                    'name'         => $row['name'],
                    'classroom_id' => $row['classroom_id'],
                    'studi_id'     => $row['studi_id'],
                    'ekskul_id'    => $row['ekskul_id'] ?? null,
                ]);
                $imported[] = $student->id;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $row, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'status'   => 'success',
            'message'  => sprintf('%d siswa berhasil diimpor', count($imported)),
            'imported' => $imported,
            'failed'   => $failed,
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Validasi gagal',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => config('app.debug') ? $e->getMessage() : 'Server error',
        ], 500);
    }
}

    /**
     * GET /api/students/classroom/{classroom_id}
     */
    public function byClassroom($classroom_id)
    {
        $students = Student::with(['classroom','ekskul','studi'])
            ->where('classroom_id', $classroom_id)->get();

        if ($students->isEmpty()) {
            return response()->json(['success'=>false,'message' => 'Tidak ada siswa di kelas ini'], 404);
        }

        return response()->json([
            'success'=>true,
            'message'=>'Daftar siswa per kelas',
            'data'=>$students
        ]);
    }

    /**
     * GET /api/students/ekskul/{ekskul_id}
     */
    public function byEkskul($ekskul_id)
    {
        $students = Student::with(['classroom','ekskul','studi'])
            ->where('ekskul_id', $ekskul_id)->get();

        if ($students->isEmpty()) {
            return response()->json(['success'=>false,'message' => 'Tidak ada siswa di ekskul ini'], 404);
        }

        return response()->json([
            'success'=>true,
            'message'=>'Daftar siswa per ekskul',
            'data'=>$students
        ]);
    }
}
