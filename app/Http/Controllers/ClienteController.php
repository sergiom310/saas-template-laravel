<?php

namespace App\Http\Controllers;

use App\Models\ClienteAgenda;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        try {
            $response = ClienteAgenda::orderBy('id_cliente', 'desc')->get();
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $response = ClienteAgenda::findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'cedula' => 'nullable|string|max:20',
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $response = ClienteAgenda::create($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $response], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cedula' => 'nullable|string|max:20',
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $cliente = ClienteAgenda::findOrFail($id);
            $cliente->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    public function destroy($id)
    {
        try {
            $cliente = ClienteAgenda::findOrFail($id);
            $cliente->delete();
            return response()->json(['success' => 'Registro eliminado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
}
