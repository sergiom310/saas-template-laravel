<?php

namespace App\Http\Controllers;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfesionalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $response = Profesional::with('especialidad')
                ->orderBy('id_profesional', 'desc')
                ->get();
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'id_especialidad' => 'required|integer|exists:agd_especialidad,id_especialidad',
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        try {
            $response = Profesional::create($request->all());
            $response->load('especialidad');
            return response()->json($response, 201);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $response = Profesional::with('especialidad')->findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'id_especialidad' => 'required|integer|exists:agd_especialidad,id_especialidad',
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        try {
            $profesional = Profesional::findOrFail($id);
            $profesional->update($request->all());
            $profesional->load('especialidad');
            return response()->json($profesional, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $profesional = Profesional::findOrFail($id);
            $profesional->delete();
            return response()->json(['message' => 'Profesional eliminado exitosamente'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
