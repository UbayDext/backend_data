<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EkskulAttendances;
use App\Models\Student;
use Illuminate\Http\Request;

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
    public function rekap(Request $request)
{
    $ekskulId  = (int) $request->query('ekskul_id');
    // terima keduanya
    $kelasId   = $request->query('kelas_id', $request->query('classroom_id'));
    $studiId   = $request->query('studi_id');
    $tanggal   = $request->query('tanggal');
    $month     = $request->query('month');

    $q = EkskulAttendance::query()
        ->where('ekskul_id', $ekskulId);

    if ($month) {
        $q->where('tanggal', 'like', $month.'%'); // YYYY-MM
    }
    if ($tanggal) {
        $q->where('tanggal', $tanggal); // YYYY-MM-DD
        // kalau kolommu date/datetime dan mau aman zona waktu:
        // $q->whereDate('tanggal', $tanggal);
    }

    // filter relasi opsional
    $q->when($kelasId, function ($q) use ($kelasId) {
        $q->whereHas('student', fn($s) => $s->where('classroom_id', $kelasId));
    });
    $q->when($studiId, function ($q) use ($studiId) {
        $q->whereHas('student', fn($s) => $s->where('studi_id', $studiId));
    });

    $agg = $q->selectRaw("
        SUM(CASE WHEN status = 'H' THEN 1 ELSE 0 END) as H,
        SUM(CASE WHEN status = 'I' THEN 1 ELSE 0 END) as I,
        SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as S,
        SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as A
    ")->first();

    return response()->json([
        'H' => (int) ($agg->H ?? 0),
        'I' => (int) ($agg->I ?? 0),
        'S' => (int) ($agg->S ?? 0),
        'A' => (int) ($agg->A ?? 0),
    ]);
}

}
