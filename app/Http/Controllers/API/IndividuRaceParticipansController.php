<?php
namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\IndividuRace;
use App\Models\IndividuRaceParticipan;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndividuRaceParticipansController extends Controller
{
    public function index(IndividuRace $race)
    {
        try {
            $query = IndividuRaceParticipan::with(['student'])
                ->where('individu_race_id', $race->id);

            $isFinished = now()->toDateString() > $race->end_date->toDateString();

            if ($isFinished) {
                $query->orderByRaw('(point1+point2+point3+point4+point5) DESC');
            } else {
                $query->orderBy('id');
            }

            $data = $query->get()->map(function (IndividuRaceParticipan $p) {
                $stu = $p->student;
                $displayName = $stu->name ?? $stu->name_students ?? $stu->nama_siswa ?? '';

                return [
                    'id'      => $p->id,
                    'student' => [
                        'id'   => $stu->id,
                        'name' => $displayName,
                    ],
                    'point1'  => (int) $p->point1,
                    'point2'  => (int) $p->point2,
                    'point3'  => (int) $p->point3,
                    'point4'  => (int) $p->point4,
                    'point5'  => (int) $p->point5,
                    'total'   => (int) ($p->point1 + $p->point2 + $p->point3 + $p->point4 + $p->point5),
                ];
            });

            return response()->json(['success' => true, 'message' => 'OK', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil peserta: '.$e->getMessage()], 500);
        }
    }

    /**
     * GET /api/individurace/{race}/candidates
     * Daftar siswa ekskul lomba yang BELUM jadi peserta.
     */
    public function candidates(IndividuRace $race)
    {
        try {
            $already = IndividuRaceParticipan::where('individu_race_id', $race->id)
                ->pluck('student_id');
            $students = Student::query()
                ->where('ekskul_id', $race->ekskul_id)
                ->whereNotIn('id', $already)
                ->orderBy('id')
                ->get();
            $data = $students->map(function ($s) {
                $displayName = $s->name ?? $s->name_students ?? $s->nama_siswa ?? '';
                return [
                    'id'        => $s->id,
                    'name'      => $displayName,
                    'ekskul_id' => $s->ekskul_id,
                ];
            });

            return response()->json(['success' => true, 'message' => 'OK', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil kandidat: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /api/individurace/{race}/participants
     * Body: { "student_ids": [1,2,3] }
     */
    public function store(Request $request, IndividuRace $race)
    {
        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        try {
            DB::transaction(function () use ($validated, $race) {
                // hanya siswa ekskul yang sama
                $validIds = Student::whereIn('id', $validated['student_ids'])
                    ->where('ekskul_id', $race->ekskul_id)
                    ->pluck('id')
                    ->all();

                foreach ($validIds as $sid) {
                    IndividuRaceParticipan::firstOrCreate(
                        ['individu_race_id' => $race->id, 'student_id' => $sid],
                        ['point1' => 0, 'point2' => 0, 'point3' => 0, 'point4' => 0, 'point5' => 0]
                    );
                }
            });

            return response()->json(['success' => true, 'message' => 'Peserta ditambahkan'], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambah peserta: '.$e->getMessage()], 422);
        }
    }

    /**
     * PUT /api/individurace/{race}/participants/{participant}
     * Body: { "point1": 50, ... } // 0–95 kelipatan 5
     */
    public function update(Request $request, IndividuRace $race, IndividuRaceParticipan $participant)
    {
        if ($participant->individu_race_id !== $race->id) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak sesuai lomba'], 404);
        }

        $rules = ['nullable', 'integer', 'between:0,95', 'multiple_of:5'];
        $validated = $request->validate([
            'point1' => $rules, 'point2' => $rules, 'point3' => $rules,
            'point4' => $rules, 'point5' => $rules,
        ]);

        try {
            $participant->fill($validated)->save();

            return response()->json([
                'success' => true,
                'message' => 'Nilai diperbarui',
                'data'    => ['id' => $participant->id, 'total' => $participant->point1 + $participant->point2 + $participant->point3 + $participant->point4 + $participant->point5]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update nilai: '.$e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/individurace/{race}/participants/{participant}
     */
    public function destroy(IndividuRace $race, IndividuRaceParticipan $participant)
    {
        if ($participant->individu_race_id !== $race->id) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak sesuai lomba'], 404);
        }

        $participant->delete();
        return response()->json(['success' => true, 'message' => 'Peserta dihapus']);
    }

    public function bulkScores(Request $request, IndividuRace $race)
{
    // Validasi input
    $pointRules = ['nullable','integer','between:0,95','multiple_of:5'];
    $data = $request->validate([
        'scores' => ['required','array','min:1'],
        'scores.*.participant_id' => ['required','integer','exists:individu_race_participantss,id'],
        'scores.*.point1' => $pointRules,
        'scores.*.point2' => $pointRules,
        'scores.*.point3' => $pointRules,
        'scores.*.point4' => $pointRules,
        'scores.*.point5' => $pointRules,
    ]);

    try {
        DB::transaction(function () use ($data, $race) {

            $ids = collect($data['scores'])->pluck('participant_id')->unique()->values();
            $raceMap = IndividuRaceParticipan::whereIn('id', $ids)
                        ->pluck('individu_race_id', 'id');
            $invalid = $raceMap->filter(fn($raceId) => (int)$raceId !== (int)$race->id)->keys();
            if ($invalid->isNotEmpty()) {
                throw new \RuntimeException('Ada participant bukan milik lomba ini: '.$invalid->implode(','));
            }
            foreach ($data['scores'] as $row) {
                $p = IndividuRaceParticipan::findOrFail($row['participant_id']);

                $payload = collect($row)
                    ->only(['point1','point2','point3','point4','point5'])
                    ->filter(fn($v) => !is_null($v))
                    ->toArray();
                if (!empty($payload)) {
                    $p->fill($payload)->save();
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Bulk nilai disimpan']);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal bulk nilai: '.$e->getMessage(),
        ], 422);
    }
}

}
