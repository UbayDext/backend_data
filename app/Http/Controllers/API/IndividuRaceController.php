<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\IndividuRace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndividuRaceController extends Controller
{
    // GET /api/individurace?lombad_id=&status=
    public function index(Request $request)
    {
        try {
            $q = IndividuRace::query();

            if ($request->filled('lombad_id')) {
                $q->where('lombad_id', $request->lombad_id);
            }

            $status = strtolower($request->query('status', ''));
            if ($status === 'berlangsung') {
                $q->berlangsung();
            } elseif ($status === 'selesai') {
                $q->selesai();
            }

            $data = $q->orderBy('start_date', 'asc')->get()->map(function (IndividuRace $b) {
                return [
                    'id'          => $b->id,
                    'name_lomba'  => $b->name_lomba,
                    'start_date'  => optional($b->start_date)->toDateString(),
                    'end_date'    => optional($b->end_date)->toDateString(),
                    'status'      => $b->status_effective,
                    'status_raw'  => $b->status,
                    'created_at'  => $b->created_at,
                    'updated_at'  => $b->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'OK',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data babak: '.$e->getMessage(),
            ], 500);
        }
    }

    // GET /api/individurace/{individurace}
    public function show(IndividuRace $individurace)
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'id'          => $individurace->id,
                'name_lomba'  => $individurace->name_lomba,
                'start_date'  => optional($individurace->start_date)->toDateString(),
                'end_date'    => optional($individurace->end_date)->toDateString(),
                'status'      => $individurace->status_effective,
                'status_raw'  => $individurace->status,
                'created_at'  => $individurace->created_at,
                'updated_at'  => $individurace->updated_at,
            ],
        ]);
    }

    // POST /api/individurace
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name_lomba' => ['required','string','max:150'],
                'ekskul_id'  => ['required','exists:ekskuls,id'],
                'start_date' => ['required','date'],
                'end_date'   => ['required','date','after_or_equal:start_date'],
                'status'     => ['sometimes', Rule::in(['berlangsung','selesai','Berlangsung','Selesai'])],
                'lombad_id'  => ['required','exists:lombads,id'],
            ]);

            // Normalisasi status ke enum DB
            if (isset($validated['status'])) {
                $validated['status'] = ucfirst(strtolower($validated['status']));
            }

            $race = IndividuRace::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Babak berhasil dibuat',
                'data'    => [
                    'id'          => $race->id,
                    'name_lomba'  => $race->name_lomba,
                    'ekskul_id'   => $race->ekskul_id,
                    'start_date'  => $race->start_date->toDateString(),
                    'end_date'    => $race->end_date->toDateString(),
                    'status'      => $race->status_effective,
                    'lombad_id'   => $race->lombad_id,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat babak: '.$e->getMessage(),
                'errors'  => method_exists($e, 'errors') ? $e->errors() : null,
            ], 422);
        }
    }

    // PUT /api/individurace/{individurace}
    public function update(Request $request, IndividuRace $individurace)
    {
        $validated = $request->validate([
            'name_lomba' => ['sometimes','string','max:150'],
            'ekskul_id'  => ['sometimes','exists:ekskuls,id'],
            'start_date' => ['sometimes','date'],
            'end_date'   => ['sometimes','date','after_or_equal:start_date'],
            'status'     => ['sometimes', Rule::in(['berlangsung','selesai','Berlangsung','Selesai'])],
            'lombad_id'  => ['sometimes','exists:lombads,id'],
        ]);

        if (isset($validated['status'])) {
            $validated['status'] = ucfirst(strtolower($validated['status']));
        }

        $individurace->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Babak berhasil diperbarui',
            'data'    => [
                'id'          => $individurace->id,
                'name_lomba'  => $individurace->name_lomba,
                'ekskul_id'   => $individurace->ekskul_id,
                'start_date'  => $individurace->start_date->toDateString(),
                'end_date'    => $individurace->end_date->toDateString(),
                'status'      => $individurace->status_effective,
                'lombad_id'   => $individurace->lombad_id,
            ],
        ]);
    }

    // DELETE /api/individurace/{individurace}
    public function destroy(IndividuRace $individurace)
    {
        $individurace->delete();
        return response()->json([
            'success' => true,
            'message' => 'Babak berhasil dihapus',
        ], 200);
    }
}
