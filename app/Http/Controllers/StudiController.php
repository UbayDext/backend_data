<?php

namespace App\Http\Controllers;

use App\Models\Studi;
use Illuminate\Http\Request;

class StudiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Studi::all();
    }

    public function show(Studi $studi)
    {
        return $studi;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json(['message' => 'Not allowed'], 403);
    }

    /**
     * Display the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Studi $studi)
    {
        return response()->json((['message' => 'Not allowed']), 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Studi $studi)
    {
        return response()->json(['message' => 'Not allowed'], 403);
    }
}
