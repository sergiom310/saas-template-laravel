<?php

namespace App\Http\Controllers;

use App\Models\FranjaHoraria;
use Illuminate\Http\Request;

class FranjaHorariaController extends Controller
{
    public function index()
    {
        try {
            $response = FranjaHoraria::orderBy('hora_inicio', 'asc')->get();
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $response = FranjaHoraria::findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        // Normalizar formato a H:i
        $data = $request->all();
        if (isset($data['hora_inicio'])) {
            $data['hora_inicio'] = substr($data['hora_inicio'], 0, 5);
        }
        if (isset($data['hora_fin'])) {
            $data['hora_fin'] = substr($data['hora_fin'], 0, 5);
        }

        $validator = \Validator::make($data, [
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $response = FranjaHoraria::create($data);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $response], 201);
    }

    public function update(Request $request, $id)
    {
        // Normalizar formato a H:i
        $data = $request->all();
        if (isset($data['hora_inicio'])) {
            $data['hora_inicio'] = substr($data['hora_inicio'], 0, 5);
        }
        if (isset($data['hora_fin'])) {
            $data['hora_fin'] = substr($data['hora_fin'], 0, 5);
        }

        $validator = \Validator::make($data, [
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $franja = FranjaHoraria::findOrFail($id);
            $franja->update($data);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    public function destroy($id)
    {
        try {
            $franja = FranjaHoraria::findOrFail($id);
            $franja->delete();
            return response()->json(['success' => 'Registro eliminado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
}
