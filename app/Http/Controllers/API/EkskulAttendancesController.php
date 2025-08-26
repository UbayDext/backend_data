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
        'classroom_id' => 'nullable|integer|exists:classrooms,id',
    ]);

    // 2. Mulai query dari tabel STUDENTS, bukan attendances
    $studentsQuery = Student::query();

    // 3. Filter siswa berdasarkan ekskul yang mereka ikuti
    $studentsQuery->where('ekskul_id', $request->input('ekskul_id'));

    // Terapkan filter tambahan jika ada
    if ($request->has('studi_id')) {
        $studentsQuery->where('studi_id', $request->input('studi_id'));
    }
    if ($request->has('classroom_id')) {
        $studentsQuery->where('classroom_id', $request->input('classroom_id'));
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
        $ekskulId  = $request->ekskul_id;
        $kelasId   = $request->kelas_id;
        $jenjangId = $request->jenjang_id;

        $query = EkskulAttendances::where('ekskul_id', $ekskulId);

        if ($request->has('month')) {
            $query->where('tanggal', 'like', $request->month . '%');
        }
        if ($request->has('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

       if ($kelasId || $jenjangId) {
        $query->when($kelasId, fn($q) =>
        $q->whereHas('student', fn($q2) => $q2->where('classroom_id', $kelasId))
            );
        $query->when($jenjangId, fn($q) =>
        $q->whereHas('student', fn($q2) => $q2->where('studi_id', $jenjangId))
            );
}
        $data = $query->selectRaw("status, count(*) as jumlah")
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return response()->json([
            'H' => $data['H'] ?? 0,
            'I' => $data['I'] ?? 0,
            'S' => $data['S'] ?? 0,
            'A' => $data['A'] ?? 0,
        ]);
    }
}
