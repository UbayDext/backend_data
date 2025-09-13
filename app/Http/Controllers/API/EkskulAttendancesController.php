<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EkskulAttendances;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
class EkskulAttendancesController extends Controller
{

        protected function isLocked(string $tanggal): bool
    {
        $tz   = config('app.timezone', 'Asia/Jakarta');
        $now  = Carbon::now($tz);
        $day  = Carbon::createFromFormat('Y-m-d', $tanggal, $tz)->startOfDay();

        // Past days selalu locked
        if ($day->lt($now->copy()->startOfDay())) {
            return true;
        }

        // Cut-off untuk hari yang sama (default: 23:59:59)
        $lockTime = config('attendance.lock_time', '23:59:59'); // ex: '18:00:00'
        $cutoff   = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d').' '.$lockTime, $tz);

        return $day->equalTo($now->copy()->startOfDay()) && $now->greaterThan($cutoff);
    }
    // Tampilkan semua siswa peserta ekskul tertentu (filter kelas & jenjang) + status presensi
  public function dailyAll(Request $request)
    {
        $request->validate([
            'ekskul_id' => 'required|integer|exists:ekskuls,id',
            'tanggal'   => 'required|date_format:Y-m-d',
            'studi_id'  => 'nullable|integer|exists:studis,id',
        ]);

        $locked = $this->isLocked($request->input('tanggal'));

        $studentsQuery = Student::query()
            ->where('ekskul_id', $request->input('ekskul_id'))
            ->when($request->filled('studi_id'), fn($q) => $q->where('studi_id', $request->input('studi_id')))
            ->with(['ekskulAttendances' => function ($q) use ($request) {
                $q->whereDate('tanggal', $request->input('tanggal'));
            }]);

        $students = $studentsQuery->get();

        $result = $students->map(function ($student) use ($locked) {
            $attendance = $student->ekskulAttendances->first();

            return [
                'student_id' => $student->id,
                'name'       => $student->name,
                'status'     => $attendance ? $attendance->status : null,
                'locked'     => $locked, // FE bisa pakai ini untuk disable UI
            ];
        });

        // Tetap kembalikan array seperti sebelumnya supaya tidak memecah FE
        return response()->json($result);
    }

    // Fast update status
   public function updateOrCreate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'ekskul_id'  => 'required|exists:ekskuls,id',
            'tanggal'    => 'required|date_format:Y-m-d',
            'status'     => 'required|in:H,I,S,A',
            'studi_id'   => 'required|exists:studis,id',
        ]);

        // TOLAK update jika hari sudah terkunci
        if ($this->isLocked($request->input('tanggal'))) {
            return response()->json([
                'message' => 'Absensi untuk tanggal tersebut sudah dikunci. Perubahan tidak diizinkan.'
            ], 423); // 423 Locked
        }

        $absen = EkskulAttendances::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'ekskul_id'  => $request->ekskul_id,
                'tanggal'    => $request->tanggal,
                'studi_id'   => $request->studi_id,
            ],
            [
                'status'     => $request->status,
            ]
        );

        return response()->json($absen);
    }


public function rekap(Request $r)
    {
        $data = $r->validate([
            'ekskul_id'    => ['required','integer','exists:ekskuls,id'],
            'tanggal'      => ['required','date_format:Y-m-d'],
            'studi_id'     => ['nullable','integer','exists:studis,id'],
            'kelas_id'     => ['nullable','integer','exists:classrooms,id'],
            'classroom_id' => ['nullable','integer','exists:classrooms,id'],
            'month'        => ['nullable','date_format:Y-m'],
        ]);

        $ekskulId = (int) $data['ekskul_id'];
        $tanggal  = $data['tanggal'];
        $studiId  = $data['studi_id'] ?? null;
        $kelasId  = $data['kelas_id'] ?? $data['classroom_id'] ?? null;

        $agg = DB::table('ekskul_attendances as ea')
            ->join('students as s', 's.id', '=', 'ea.student_id')
            ->where('ea.ekskul_id', $ekskulId)
            ->whereDate('ea.tanggal', $tanggal)
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
