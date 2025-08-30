<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lombad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LombadController extends Controller
{
    /**
     * GET /api/lombads
     * Query (opsional): ekskul_id, status (Individu|Team), studi_id (via relasi ekskul.studi_id)
     * Tambah ?debug=1 untuk keluarkan info diagnostik.
     */
 public function index(Request $r)
{
    // Kumpulkan SQL kalau debug
    $queries = [];
    if ($r->boolean('debug')) {
        \DB::listen(function ($q) use (&$queries) {
            $queries[] = ['sql' => $q->sql, 'bindings' => $q->bindings];
        });
    }

    $q = \App\Models\Lombad::query()
        ->with('ekskul')
        ->latest('id');

    // Terapkan filter hanya jika ada NILAI
    $q->when($r->filled('ekskul_id'), fn ($x) => $x->where('ekskul_id', (int) $r->ekskul_id));
    $q->when($r->filled('status'),    fn ($x) => $x->where('status', $r->status));
    $q->when($r->filled('studi_id'),  fn ($x) =>
        $x->whereHas('ekskul', fn ($y) => $y->where('studi_id', (int) $r->studi_id))
    );

    // NOTE: kalau host kamu pakai read/write split dan write DB beda/empty,
    // coba NONAKTIFKAN baris di bawah ini. Untuk tes, aku matikan dulu.
    // $data = $q->useWritePdo()->get();
    $data = $q->get();

    // RAW fallback jika Eloquent kosong
    if ($data->isEmpty()) {
        $raw = \DB::select('SELECT id, name, status, ekskul_id, created_at, updated_at FROM lombads ORDER BY id DESC LIMIT 100');
        if ($r->boolean('debug')) {
            return response()->json([
                'success' => true,
                'message' => 'DEBUG lombads (RAW fallback)',
                'debug'   => [
                    'request_query' => $r->query(),
                    'has_filled'    => [
                        'has_ekskul_id'    => $r->has('ekskul_id'),
                        'filled_ekskul_id' => $r->filled('ekskul_id'),
                        'has_status'       => $r->has('status'),
                        'filled_status'    => $r->filled('status'),
                        'has_studi_id'     => $r->has('studi_id'),
                        'filled_studi_id'  => $r->filled('studi_id'),
                    ],
                    'eloq_result_count' => 0,
                    'raw_count_table'   => \DB::table('lombads')->count(),
                    'sql'               => $queries,
                ],
                'fallback_used' => true,
                'data'          => $raw,
            ], 200)->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Daftar lomba (RAW fallback).',
            'fallback_used' => true,
            'data'          => $raw,
        ], 200)->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
    }

    // Respon normal
    $resp = [
        'success'       => true,
        'message'       => 'Daftar lomba berhasil diambil.',
        'fallback_used' => false,
        'data'          => $data,
    ];

    if ($r->boolean('debug')) {
        $resp['debug'] = [
            'request_query' => $r->query(),
            'result_count'  => $data->count(),
            'first_result'  => $data->first(),
            'sql'           => $queries,
        ];
        $resp['message'] = 'DEBUG lombads';
    }

    return response()->json($resp, 200)
        ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma','no-cache');
}


    /**
     * POST /api/lombads
     * Body: name, status (Individu|Team), ekskul_id (exists: ekskuls.id)
     */
    public function store(Request $r)
    {
        $validated = $r->validate([
            'name'      => ['required', 'string', 'max:255'],
            'status'    => ['required', 'in:Individu,Team'],
            'ekskul_id' => ['required', 'exists:ekskuls,id'],
        ]);

        $lomba = Lombad::create($validated);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil ditambahkan.',
            'data'    => $lomba,
        ], 201);
    }

    /**
     * GET /api/lombads/{id}
     */
    public function show($id)
    {
        $lomba = Lombad::with('ekskul')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail lomba berhasil diambil.',
            'data'    => $lomba,
        ], 200);
    }

    /**
     * PUT/PATCH /api/lombads/{id}
     */
    public function update(Request $r, $id)
    {
        $lomba = Lombad::findOrFail($id);

        $validated = $r->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'status'    => ['sometimes', 'required', 'in:Individu,Team'],
            'ekskul_id' => ['sometimes', 'required', 'exists:ekskuls,id'],
        ]);

        $lomba->update($validated);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil diperbarui.',
            'data'    => $lomba,
        ], 200);
    }

    /**
     * DELETE /api/lombads/{id}
     */
    public function destroy($id)
    {
        $lomba = Lombad::findOrFail($id);
        $lomba->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil dihapus.',
        ], 200);
    }
}
