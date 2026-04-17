<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class GastoController extends Controller
{
    public function index(): JsonResponse
    {
        $gastos = Gasto::orderByDesc('fecha')->get();
        return response()->json($gastos);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $gasto = Gasto::create($request->only(['descripcion', 'monto', 'fecha']));
        return response()->json($gasto, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $gasto = Gasto::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $gasto->update($request->only(['descripcion', 'monto', 'fecha']));
        return response()->json($gasto);
    }

    public function destroy($id): JsonResponse
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->delete();
        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }
}
