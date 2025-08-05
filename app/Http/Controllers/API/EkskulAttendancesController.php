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
    $ekskulId  = $request->ekskul_id;
    $tanggal   = $request->tanggal;
    $kelasId   = $request->kelas_id;
    $jenjangId = $request->studi_id;

    // Ambil student_id yang pernah ikut ekskul ini
    $studentIds = EkskulAttendances::where('ekskul_id', $ekskulId)
        ->when($jenjangId, fn($q) => $q->where('studi_id', $jenjangId))
        ->pluck('student_id')
        ->unique();

    // Ambil siswa dengan relasi
    $students = Student::with(['classroom', 'studi'])
        ->whereIn('id', $studentIds)
        ->get();

    // Ambil data presensi siswa untuk ekskul dan tanggal tertentu
    $attendance = EkskulAttendances::where('ekskul_id', $ekskulId)
        ->where('tanggal', $tanggal)
        ->when($jenjangId, fn($q) => $q->where('studi_id', $jenjangId))
        ->get()
        ->keyBy('student_id');

    // Gabungkan student dan status
    $result = $students->map(function($student) use ($attendance) {
        $att = $attendance->get($student->id);
        return [
            'student_id' => $student->id,
            'name'       => $student->name,
            'classroom'  => $student->classroom->name ?? null,
            'studi'      => $student->studi?->nama_studi ?? null,
            'status'     => $att ? $att->status : null,
        ];
    });

    return response()->json($result);
}

    // Fast update status
    public function updateOrCreate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'ekskul_id'  => 'required|exists:ekskuls,id',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:H,I,S,A',
            'studi_id'   => 'required|exists:studis,id'
        ]);

        $absen = EkskulAttendances::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'ekskul_id'  => $request->ekskul_id,
                'tanggal'    => $request->tanggal,
                'studi_id'   => $request->studi_id
            ],
            [
                'status'     => $request->status,
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
