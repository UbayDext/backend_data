<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EkskulAttendances;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class EkskulAttendancesController extends Controller
{
    // Tampilkan semua siswa peserta ekskul tertentu (filter kelas & jenjang) + status presensi
public function dailyAll(Request $request)
{
    // 1. Validasi input (ini sudah benar)
    $request->validate([
        'ekskul_id' => 'required|integer|exists:ekskuls,id',
        'tanggal' => 'required|date_format:Y-m-d',
        'studi_id' => 'nullable|integer|exists:studis,id',
    ]);

    // 2. Mulai query dari tabel STUDENTS, bukan attendances
    $studentsQuery = Student::query();

    // 3. Filter siswa berdasarkan ekskul yang mereka ikuti
    $studentsQuery->where('ekskul_id', $request->input('ekskul_id'));

    // Terapkan filter tambahan jika ada
    if ($request->has('studi_id')) {
        $studentsQuery->where('studi_id', $request->input('studi_id'));
    }

    // 4. Ambil daftar siswa yang relevan, DAN sertakan (load) relasi absensi
    //    NAMUN, relasi absensi itu HANYA untuk tanggal yang diminta.
    $students = $studentsQuery->with(['ekskulAttendances' => function ($query) use ($request) {
        $query->whereDate('tanggal', $request->input('tanggal'));
    }])->get();

    // 5. Transformasi data agar sesuai dengan yang dibutuhkan Flutter
    //    Kita butuh format: { student_id, name, status }
    $result = $students->map(function ($student) {
        // Cek apakah ada data absensi untuk siswa ini (hasil dari 'with' di atas)
        $attendance = $student->ekskulAttendances->first();

        return [
            'student_id' => $student->id,
            'name' => $student->name,
            // Jika ada data absensi, ambil statusnya. Jika tidak, statusnya null.
            'status' => $attendance ? $attendance->status : null,
        ];
    });

    // 6. Kembalikan hasil yang sudah ditransformasi
    return response()->json($result);
}

    // Fast update status
  public function updateOrCreate(Request $request)
{
    $request->validate([
        'student_id'   => 'required|exists:students,id',
        'ekskul_id'    => 'required|exists:ekskuls,id',
        'tanggal'      => 'required|date',
        'status'       => 'required|in:H,I,S,A',
        'studi_id'     => 'required|exists:studis,id',
    ]);

    $absen = EkskulAttendances::updateOrCreate(
        [
            'student_id'   => $request->student_id,
            'ekskul_id'    => $request->ekskul_id,
            'tanggal'      => $request->tanggal,
            'studi_id'     => $request->studi_id,
        ],
        [
            'status'       => $request->status,
        ]
    );

    return response()->json($absen);
}


    // Rekap harian/bulanan (optional: bisa tambah filter kelas/jenjang)
   public function rekap(Request $r)
{
    $ekskulId  = (int) $r->get('ekskul_id');
    $tanggal   = $r->get('tanggal'); // 'YYYY-MM-DD'
    $studiId   = $r->get('studi_id'); // nullable
    $kelasId   = $r->get('kelas_id', $r->get('classroom_id')); // alias

    // VALIDASI minimum
    if (!$ekskulId || !$tanggal) {
        return response()->json(['message' => 'ekskul_id & tanggal wajib'], 422);
    }

    $agg = DB::table('ekskul_attendances as ea')
        ->join('students as s', 's.id', '=', 'ea.student_id')
        ->where('ea.ekskul_id', $ekskulId)
        ->whereDate('ea.tanggal', $tanggal)               // <= penting!
        ->when($studiId, fn($q) => $q->where('s.studi_id', $studiId))
        ->when($kelasId, fn($q) => $q->where('s.classroom_id', $kelasId))
        ->selectRaw("
            SUM(CASE WHEN ea.status='H' THEN 1 ELSE 0 END) as H,
            SUM(CASE WHEN ea.status='I' THEN 1 ELSE 0 END) as I,
            SUM(CASE WHEN ea.status='S' THEN 1 ELSE 0 END) as S,
            SUM(CASE WHEN ea.status='A' THEN 1 ELSE 0 END) as A
        ")
        ->first();

    return response()->json([
        'H' => (int) ($agg->H ?? 0),
        'I' => (int) ($agg->I ?? 0),
        'S' => (int) ($agg->S ?? 0),
        'A' => (int) ($agg->A ?? 0),
    ]);
}

}
