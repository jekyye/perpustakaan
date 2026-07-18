<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Http\Requests\BukuRequest;
use Illuminate\Http\Request;

class BukuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager loading kategori to prevent N+1
        $buku = Buku::with('kategori')->get();
        return response()->json(['data' => $buku], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BukuRequest $request)
    {
        $buku = Buku::create($request->validated());
        return response()->json(['message' => 'Buku created successfully', 'data' => $buku], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $buku = Buku::with('kategori')->find($id);
        if (!$buku) {
            return response()->json(['message' => 'Buku not found'], 404);
        }
        return response()->json(['data' => $buku], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BukuRequest $request, string $id)
    {
        $buku = Buku::find($id);
        if (!$buku) {
            return response()->json(['message' => 'Buku not found'], 404);
        }
        
        $buku->update($request->validated());
        return response()->json(['message' => 'Buku updated successfully', 'data' => $buku], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!in_array($request->user()->role, ['admin', 'petugas'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $buku = Buku::find($id);
        if (!$buku) {
            return response()->json(['message' => 'Buku not found'], 404);
        }

        $buku->delete();
        return response()->json(['message' => 'Buku deleted successfully'], 200);
    }
}
