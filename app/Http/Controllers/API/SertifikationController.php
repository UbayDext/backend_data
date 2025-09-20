<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SertifikationResource;
use App\Models\Sertifikation;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
class SertifikationController extends Controller
{
    // GET /api/sertifikations?student_id=..&ekskul_id=..&studi_id=..&classroom_id=..&from=YYYY-MM-DD&to=YYYY-MM-DD
    public function index(Request $r)
    {
        $q = Sertifikation::with(['student','classroom','ekskul'])
            ->student($r->student_id)
            ->ekskul($r->ekskul_id)
            ->studi($r->studi_id)
            ->classroom($r->classroom_id)
            ->betweenDate($r->from, $r->to)
            ->latest();

        // pakai pagination supaya aman di FE
        return SertifikationResource::collection($q->paginate($r->get('per_page', 15)));
    }

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

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('sertifikats','public')
            : null;

        $sertifikat = Sertifikation::create(array_merge($validated, [
            'file_path' => $filePath,
        ]));

        return new SertifikationResource($sertifikat->load(['student','classroom','ekskul']));
    }

    public function show($id)
    {
        $sertifikat = Sertifikation::with(['student','classroom','ekskul'])->findOrFail($id);
        return new SertifikationResource($sertifikat);
    }

    public function update(Request $request, $id)
    {
        $sertifikat = Sertifikation::findOrFail($id);

        $validated = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'student_id'    => 'sometimes|required|exists:students,id',
            'studi_id'      => 'nullable|exists:studis,id',
            'ekskul_id'     => 'nullable|exists:ekskuls,id',
            'classroom_id'  => 'nullable|exists:classrooms,id',
            'file'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5048',
        ]);

        $sertifikat->fill($validated);

        if ($request->hasFile('file')) {
            if ($sertifikat->file_path && Storage::disk('public')->exists($sertifikat->file_path)) {
                Storage::disk('public')->delete($sertifikat->file_path);
            }
            $sertifikat->file_path = $request->file('file')->store('sertifikats','public');
        }

        $sertifikat->save();

        return new SertifikationResource($sertifikat->load(['student','classroom','ekskul']));
    }

    public function destroy($id)
    {
        $sertifikat = Sertifikation::findOrFail($id);
        if ($sertifikat->file_path && Storage::disk('public')->exists($sertifikat->file_path)) {
            Storage::disk('public')->delete($sertifikat->file_path);
        }
        $sertifikat->delete();
        return response()->json(['message' => 'deleted']);
    }

    // Rekap jumlah sertifikat per student (dengan filter opsional)
   public function counts(Request $r)
{
    $q = Sertifikation::query()
        ->when($r->studi_id, fn($q,$v)=>$q->where('studi_id',$v))
        ->when($r->classroom_id, fn($q,$v)=>$q->where('classroom_id',$v))
        ->when($r->ekskul_id, fn($q,$v)=>$q->where('ekskul_id',$v));

    $data = $q->select('student_id', DB::raw('COUNT(*) as total'))
              ->groupBy('student_id')
              ->get();

    return response()->json(['data'=>$data]);
}


    // Opsional: ambil semua sertifikat milik satu siswa
    // GET /api/sertifikations/by-student/{student}?ekskul_id=&studi_id=&classroom_id=
    public function byStudent(Request $r, $studentId)
    {
        $q = Sertifikation::with(['student','classroom','ekskul'])
            ->student($studentId)
            ->ekskul($r->ekskul_id)
            ->studi($r->studi_id)
            ->classroom($r->classroom_id)
            ->betweenDate($r->from, $r->to)
            ->latest();

        return SertifikationResource::collection($q->paginate($r->get('per_page', 15)));
    }
    public function studentsWithCertificate(Request $request)
{
    $perPage = min($request->integer('per_page', 15), 100);

    $q = Student::query()
        ->with([
            'classroom:id,name',
            'ekskul:id,nama_ekskul',
            // GANTI ini:
            // 'studi:id,name',
            'studi:id,nama_studi',
        ])
        ->withCount('sertifikations')
        ->whereHas('sertifikations', function ($sq) use ($request) {
            $from = $request->date('from');
            $to   = $request->date('to');
            if ($from && $to)      $sq->whereBetween('created_at', [$from, $to]);
            elseif ($from)         $sq->whereDate('created_at', '>=', $from);
            elseif ($to)           $sq->whereDate('created_at', '<=', $to);
        });

    if ($request->filled('classroom_id')) $q->where('classroom_id', $request->integer('classroom_id'));
    if ($request->filled('ekskul_id'))    $q->where('ekskul_id',    $request->integer('ekskul_id'));
    if ($request->filled('studi_id'))     $q->where('studi_id',     $request->integer('studi_id'));
    if ($request->filled('search')) {
        $search = strtolower((string) $request->input('search'));
        $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
    }

    $page = $q->orderBy('name')->paginate($perPage);

    return response()->json([
        'success' => true,
        'message' => 'Daftar siswa yang memiliki sertifikat',
        'meta'    => [
            'total'        => $page->total(),
            'per_page'     => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
        ],
        'data'    => $page->items(),
    ]);
}

  public function studentsWithCertificateLatest(Request $request)
{
    $perPage = min($request->integer('per_page', 15), 100);

    $q = Student::query()
        ->with([
            'classroom:id,name',
            'ekskul:id,nama_ekskul',
            // kolom Studi yang benar
            'studi:id,nama_studi',
            // <- penting: pakai closure agar kolom dari tabel sertifikations diprefix
            'latestSertifikation' => function ($q2) {
                $q2->select(
                    'sertifikations.id',
                    'sertifikations.student_id',   // diprefix -> tidak ambigu
                    'sertifikations.title',
                    'sertifikations.file_path',
                    'sertifikations.created_at'
                );
            },
        ])
        ->withCount('sertifikations')
        ->whereHas('sertifikations', function ($sq) use ($request) {
            $from = $request->date('from');
            $to   = $request->date('to');
            if ($from && $to)      $sq->whereBetween('created_at', [$from, $to]);
            elseif ($from)         $sq->whereDate('created_at', '>=', $from);
            elseif ($to)           $sq->whereDate('created_at', '<=', $to);
        });

    if ($request->filled('classroom_id')) $q->where('classroom_id', $request->integer('classroom_id'));
    if ($request->filled('ekskul_id'))    $q->where('ekskul_id',    $request->integer('ekskul_id'));
    if ($request->filled('studi_id'))     $q->where('studi_id',     $request->integer('studi_id'));
    if ($request->filled('search')) {
        $search = strtolower((string) $request->input('search'));
        $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
    }

    $page = $q->orderBy('name')->paginate($perPage);

    return response()->json([
        'success' => true,
        'message' => 'Daftar siswa yang memiliki sertifikat (dengan sertifikat terbaru)',
        'meta'    => [
            'total'        => $page->total(),
            'per_page'     => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
        ],
        'data'    => $page->items(),
    ]);
}

}
