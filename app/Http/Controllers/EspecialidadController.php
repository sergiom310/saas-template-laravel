<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    public function index()
    {
        try {
            $response = Especialidad::orderBy('id_especialidad', 'desc')->get();
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $response = Especialidad::findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
        ]);

        try {
            $response = Especialidad::create($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $response], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
        ]);

        try {
            $especialidad = Especialidad::findOrFail($id);
            $especialidad->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    public function destroy($id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);
            $especialidad->delete();
            return response()->json(['success' => 'Registro eliminado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function activate(Request $request, $id)
    {
        try {
            $especialidad = Especialidad::findOrFail($id);
            $especialidad->update([
                'activo' => $request['activo']
            ]);
            return response()->json(['success' => 'Registro actualizado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }
    }
}
