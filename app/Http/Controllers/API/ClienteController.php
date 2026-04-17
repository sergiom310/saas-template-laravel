<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = Cliente::with('tipoVehiculo')->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'nom_cli' => 'required|string|max:80',
            'telefono' => 'nullable|string|max:40',
            'tipo_vehi' => 'nullable|exists:tipo_vehiculo,id',
            'valor_mensual' => 'nullable|numeric',
            'placa' => 'nullable|string|max:20',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
            'estado' => 'nullable|in:A,I',
            'imp' => 'nullable|in:S,N',
        ]);

        try {
            $response = Cliente::create($request->all());
            $response->load('tipoVehiculo');
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
        $response = Cliente::with('tipoVehiculo')->whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'nom_cli' => 'required|string|max:80',
            'telefono' => 'nullable|string|max:40',
            'tipo_vehi' => 'nullable|exists:tipo_vehiculo,id',
            'valor_mensual' => 'nullable|numeric',
            'placa' => 'nullable|string|max:20',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
            'estado' => 'nullable|in:A,I',
            'imp' => 'nullable|in:S,N',
        ]);

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->update($request->all());
            $cliente->load('tipoVehiculo');
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente', 'data' => $cliente], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->delete();
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Activate/Deactivate a client.
     */
    public function activate(Request $request, $id)
    {
        $this->middleware('permission:admin.update');

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->update([
                'estado' => $request['estado']
            ]);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado'], 200);
    }
}
