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
     * Query opsional: ?ekskul_id=, ?status=Individu|Team, ?studi_id= (via relasi ekskul.studi_id)
     * Tambah ?debug=1 untuk output diagnostik (param, has/filled, SQL, counts).
     * Jika hasil Eloquent kosong tapi data RAW ada, fallback akan dipakai (ditandai "fallback_used": true).
     */
    public function index(Request $r)
    {
        // KUMPULKAN SQL utk debug (hanya bila ?debug=1)
        $queries = [];
        if ($r->boolean('debug')) {
            DB::listen(function ($q) use (&$queries) {
                $queries[] = ['sql' => $q->sql, 'bindings' => $q->bindings];
            });
        }

        // ---- Eloquent query standar (tanpa scope nyasar) ----
        $q = Lombad::query()
            ->with('ekskul')
            ->latest('id');

        // Terapkan filter HANYA jika ada nilainya (hindari param kosong)
        $q->when($r->filled('ekskul_id'), fn ($x) => $x->where('ekskul_id', (int) $r->ekskul_id));
        $q->when($r->filled('status'),    fn ($x) => $x->where('status', $r->status));
        $q->when($r->filled('studi_id'),  fn ($x) =>
            $x->whereHas('ekskul', fn ($y) => $y->where('studi_id', (int) $r->studi_id))
        );

        // Hindari replica lag
        $eloq = $q->useWritePdo()->get();

        // ---- RAW fallback: kalau Eloquent 0 tapi tabel sebenarnya ada data ----
        $fallbackUsed = false;
        if ($eloq->isEmpty()) {
            $raw = DB::connection()->select('
                SELECT id, name, status, ekskul_id, created_at, updated_at
                FROM lombads
                ORDER BY id DESC
                LIMIT 100
            ');
            if (count($raw) > 0) {
                $fallbackUsed = true;
                // Kembalikan RAW apa adanya (tanpa relasi) supaya terlihat datanya ada
                if ($r->boolean('debug')) {
                    return response()->json([
                        'success'  => true,
                        'message'  => 'DEBUG lombads (RAW fallback used)',
                        'debug'    => [
                            'app_env'       => config('app.env'),
                            'db_connection' => config('database.default'),
                            'db_database'   => DB::connection()->getDatabaseName(),
                            'auth_user_id'  => optional(auth()->user())->id,
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
                            'raw_count_table'   => DB::table('lombads')->count(),
                            'sql'               => $queries,
                        ],
                        'fallback_used' => true,
                        'data' => $raw,
                    ], 200);
                }

                return response()->json([
                    'success'       => true,
                    'message'       => 'Daftar lomba berhasil diambil (RAW fallback).',
                    'fallback_used' => true,
                    'data'          => $raw,
                ], 200);
            }
        }

        // ---- Normal response (Eloquent berhasil) ----
        if ($r->boolean('debug')) {
            return response()->json([
                'success'  => true,
                'message'  => 'DEBUG lombads',
                'debug'    => [
                    'app_env'       => config('app.env'),
                    'db_connection' => config('database.default'),
                    'db_database'   => DB::connection()->getDatabaseName(),
                    'auth_user_id'  => optional(auth()->user())->id,
                    'request_query' => $r->query(),
                    'has_filled'    => [
                        'has_ekskul_id'    => $r->has('ekskul_id'),
                        'filled_ekskul_id' => $r->filled('ekskul_id'),
                        'has_status'       => $r->has('status'),
                        'filled_status'    => $r->filled('status'),
                        'has_studi_id'     => $r->has('studi_id'),
                        'filled_studi_id'  => $r->filled('studi_id'),
                    ],
                    'result_count'  => $eloq->count(),
                    'first_result'  => $eloq->first(),
                    'sql'           => $queries,
                ],
                'fallback_used' => $fallbackUsed,
                'data'          => $eloq,
            ], 200);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Daftar lomba berhasil diambil.',
            'fallback_used' => $fallbackUsed,
            'data'          => $eloq,
        ], 200);
    }

    /** POST /api/lombads */
    public function store(Request $r)
    {
        $v = $r->validate([
            'name'      => ['required','string','max:255'],
            'status'    => ['required','in:Individu,Team'],
            'ekskul_id' => ['required','exists:ekskuls,id'],
        ]);

        $lomba = Lombad::create($v);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil ditambahkan.',
            'data'    => $lomba,
        ], 201);
    }

    /** GET /api/lombads/{id} */
    public function show($id)
    {
        $lomba = Lombad::with('ekskul')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail lomba berhasil diambil.',
            'data'    => $lomba,
        ], 200);
    }

    /** PUT/PATCH /api/lombads/{id} */
    public function update(Request $r, $id)
    {
        $lomba = Lombad::findOrFail($id);

        $v = $r->validate([
            'name'      => ['sometimes','required','string','max:255'],
            'status'    => ['sometimes','required','in:Individu,Team'],
            'ekskul_id' => ['sometimes','required','exists:ekskuls,id'],
        ]);

        $lomba->update($v);
        $lomba->load('ekskul');

        return response()->json([
            'success' => true,
            'message' => 'Lomba berhasil diperbarui.',
            'data'    => $lomba,
        ], 200);
    }

    /** DELETE /api/lombads/{id} */
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
