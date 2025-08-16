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
     * Menampilkan semua grup tim berdasarkan ID Lomba.
     * URL: GET /api/lombads/{lombad_id}/team-races
     */
    public function index($lombad_id)
    {
        // Cek apakah lombad (kompetisi) ada
        $lombad = Lombad::find($lombad_id);
        if (!$lombad) {
            return response()->json([
                'success' => false,
                'message' => 'Lombad tidak ditemukan.',
            ], 404);
        }

        // Ambil semua team race yang berelasi dengan lombad_id
        $teamRaces = TeamRace::where('lombad_id', $lombad_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar grup tim berhasil diambil.',
            'data' => $teamRaces
        ], 200);
    }

    /**
     * Menyimpan data grup tim baru.
     * URL: POST /api/team-races
     */
    public function store(Request $request)
    {
        // Validasi input dari request
        $validator = Validator::make($request->all(), [
            'name_group' => 'required|string|max:255',
            'name_team1' => 'required|string|max:255',
            'name_team2' => 'required|string|max:255',
            'name_team3' => 'required|string|max:255',
            'name_team4' => 'required|string|max:255',
            'name_team5' => 'required|string|max:255',
            'lombad_id'  => 'required|exists:lombads,id', // Pastikan lombad_id ada di tabel lombads
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }


        $data = $request->all();
        $data['champion'] = 'Belum ada pemenang'; // Sesuai dengan UI Anda

        $teamRace = TeamRace::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Grup tim berhasil ditambahkan.',
            'data' => $teamRace
        ], 201); // 201 Created
    }

    /**
     * Menampilkan detail satu grup tim.
     * URL: GET /api/team-races/{id}
     */
    public function show($id)
    {
        $teamRace = TeamRace::find($id);

        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail grup tim berhasil diambil.',
            'data' => $teamRace
        ], 200);
    }

    /**
     * Mengupdate data champion pada grup tim.
     * URL: PUT /api/team-races/{id}/set-champion
     */
    public function setChampion(Request $request, $id)
    {
        $teamRace = TeamRace::find($id);

        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan.',
            ], 404);
        }

        // Validasi bahwa nama champion harus salah satu dari 5 tim yang ada
        $validator = Validator::make($request->all(), [
            'champion' => [
                'required',
                'string',
                Rule::in([
                    trim($teamRace->name_team1),
                    trim($teamRace->name_team2),
                    trim($teamRace->name_team3),
                    trim($teamRace->name_team4),
                    trim($teamRace->name_team5),
                ]),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Nama champion tidak valid atau tidak ada dalam grup ini.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update hanya field champion
        $teamRace->champion = $request->champion;
        $teamRace->save();

        return response()->json([
            'success' => true,
            'message' => 'Juara berhasil ditentukan.',
            'data' => $teamRace
        ], 200);
    }

  public function update(Request $request, $id)
{
    // 1. Cari data yang akan diupdate
    $teamRace = TeamRace::find($id);

    // Jika data tidak ditemukan, kembalikan error 404
    if (!$teamRace) {
        return response()->json([
            'success' => false,
            'message' => 'Grup tim tidak ditemukan.',
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'name_group' => 'sometimes|required|string|max:255',
        'name_team1' => 'sometimes|required|string|max:255',
        'name_team2' => 'sometimes|required|string|max:255',
        'name_team3' => 'sometimes|required|string|max:255',
        'name_team4' => 'sometimes|required|string|max:255',
        'name_team5' => 'sometimes|required|string|max:255',
        'lombad_id'  => 'sometimes|required|exists:lombads,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors()
        ], 422);
    }

    // 3. Lakukan update data
    $teamRace->update($request->all());

    // 4. Kembalikan respons sukses dengan data yang sudah diperbarui
    return response()->json([
        'success' => true,
        'message' => 'Grup tim berhasil diperbarui.',
        'data' => $teamRace
    ], 200); // 200 OK
}

    /**
     * Menghapus grup tim.
     * URL: DELETE /api/team-races/{id}
     */
    public function destroy($id)
    {
        $teamRace = TeamRace::find($id);

        if (!$teamRace) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tim tidak ditemukan.',
            ], 404);
        }

        $teamRace->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grup tim berhasil dihapus.',
        ], 200);
    }
}
