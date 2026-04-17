<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TarifaRegla;
use Illuminate\Http\Request;

class TarifaReglaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = TarifaRegla::with(['tarifa', 'tipoVehiculo'])->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'tarifa_id' => 'required|exists:tarifa,id',
            'tipo_vehiculo_id' => 'required|exists:tipo_vehiculo,id',
            'minutos_desde' => 'required|integer|min:0',
            'minutos_hasta' => 'required|integer|min:0|gte:minutos_desde',
            'contexto' => 'required|in:TOTAL,FRACCION',
            'tipo_calculo' => 'required|in:FIJO,POR_HORA,COBRO_LIBRE',
            'valor' => 'required|numeric|min:0',
            'prioridad' => 'integer|min:1'
        ]);

        try {
            $response = TarifaRegla::create($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json([
            "data" => $response
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->middleware('permission:admin.index');
        $response = TarifaRegla::with(['tarifa', 'tipoVehiculo'])->whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'id' => 'required|exists:tarifa_regla,id',
            'tarifa_id' => 'required|exists:tarifa,id',
            'tipo_vehiculo_id' => 'required|exists:tipo_vehiculo,id',
            'minutos_desde' => 'required|integer|min:0',
            'minutos_hasta' => 'required|integer|min:0|gte:minutos_desde',
            'contexto' => 'required|in:TOTAL,FRACCION',
            'tipo_calculo' => 'required|in:FIJO,POR_HORA,COBRO_LIBRE',
            'valor' => 'required|numeric|min:0',
            'prioridad' => 'integer|min:1'
        ]);

        try {
            $tarifaRegla = TarifaRegla::findOrFail($request->id);
            $tarifaRegla->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');
        $tarifaRegla = TarifaRegla::findOrFail($id);
        
        $tarifaRegla->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }
}
