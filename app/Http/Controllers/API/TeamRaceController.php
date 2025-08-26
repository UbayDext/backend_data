<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lombad;
use App\Models\TeamRace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamRaceController extends Controller
{
    /**
     * GET: semua grup tim berdasarkan lombad_id
     */
    public function index($lombad_id)
    {
        $lombad = Lombad::find($lombad_id);
        if (!$lombad) {
            return response()->json([
                'success' => false,
                'message' => 'Lomba tidak ditemukan',
            ], 404);
        }

        $teamRaces = TeamRace::where('lombad_id', $lombad_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar grup tim berhasil diambil',
            'data' => $teamRaces,
        ], 200);
    }

    /**
     * POST: tambah grup tim baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_group' => 'required|string|max:255',
            'name_team1' => 'required|string|max:255',
            'name_team2' => 'required|string|max:255',
            'name_team3' => 'required|string|max:255',
            'name_team4' => 'required|string|max:255',
            'lombad_id'  => 'required|exists:lombads,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teamRace = TeamRace::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Grup tim berhasil ditambahkan',
            'data' => $teamRace,
        ], 201);
    }

    /**
     * GET: detail grup tim
     */
    public function show($id)
    {
        $teamRace = TeamRace::find($id);

        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail grup tim berhasil diambil',
            'data' => $teamRace,
        ], 200);
    }

    /**
     * POST: set winner match 1
     */
    public function setWinnerMatch1(Request $request, $id)
    {
        $teamRace = TeamRace::find($id);
        if (!$teamRace) {
            return response()->json(['success' => false, 'message' => 'Grup tim tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'winner_match1' => [
                'required',
                'string',
                Rule::in([trim($teamRace->name_team1), trim($teamRace->name_team2)]),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Pemenang tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teamRace->winner_match1 = $request->winner_match1;
        $teamRace->save();

        return response()->json([
            'success' => true,
            'message' => 'Pemenang match 1 ditentukan',
            'data' => $teamRace,
        ], 200);
    }

    /**
     * POST: set winner match 2
     */
    public function setWinnerMatch2(Request $request, $id)
    {
        $teamRace = TeamRace::find($id);
        if (!$teamRace) {
            return response()->json(['success' => false, 'message' => 'Grup tim tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'winner_match2' => [
                'required',
                'string',
                Rule::in([trim($teamRace->name_team3), trim($teamRace->name_team4)]),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Pemenang tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teamRace->winner_match2 = $request->winner_match2; // ✅ fix bug
        $teamRace->save();

        return response()->json([
            'success' => true,
            'message' => 'Pemenang match 2 ditentukan',
            'data' => $teamRace,
        ], 200);
    }

    /**
     * PUT: set champion dari semifinal winners
     */
    public function setChampion(Request $request, $id)
    {
        $teamRace = TeamRace::find($id);
        if (!$teamRace) {
            return response()->json(['success' => false, 'message' => 'Grup tim tidak ditemukan'], 404);
        }

        if (!$teamRace->winner_match1 || !$teamRace->winner_match2) {
            return response()->json(['success' => false, 'message' => 'Pemenang semifinal belum ditentukan'], 400);
        }

        $validator = Validator::make($request->all(), [
            'champion' => [
                'required',
                'string',
                Rule::in([$teamRace->winner_match1, $teamRace->winner_match2]),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Champion tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teamRace->champion = $request->champion;
        $teamRace->save();

        return response()->json([
            'success' => true,
            'message' => 'Champion berhasil ditentukan',
            'data' => $teamRace,
        ], 200);
    }

    /**
     * PUT: update data grup tim (bisa ubah nama group atau tim)
     */
    public function update(Request $request, $id)
    {
        $teamRace = TeamRace::find($id);
        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name_group' => 'sometimes|required|string|max:255',
            'name_team1' => 'sometimes|required|string|max:255',
            'name_team2' => 'sometimes|required|string|max:255',
            'name_team3' => 'sometimes|required|string|max:255',
            'name_team4' => 'sometimes|required|string|max:255',
            'lombad_id'  => 'sometimes|required|exists:lombads,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teamRace->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Grup tim berhasil diperbarui',
            'data' => $teamRace,
        ], 200);
    }

    /**
     * DELETE: hapus grup tim
     */
    public function destroy($id)
    {
        $teamRace = TeamRace::find($id);
        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan',
            ], 404);
        }

        $teamRace->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grup tim berhasil dihapus',
        ], 200);
    }
}
