<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = MetodoPago::orderBy('detalle', 'asc')->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'detalle' => 'required|string|max:100|unique:metodo_pago,detalle',
            'activo' => 'boolean'
        ]);

        try {
            $response = MetodoPago::create($request->all());
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
        $response = MetodoPago::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'detalle' => 'required|string|max:100|unique:metodo_pago,detalle,' . $id,
            'activo' => 'boolean'
        ]);

        try {
            $metodoPago = MetodoPago::findOrFail($id);
            $metodoPago->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json($metodoPago, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');
        $metodoPago = MetodoPago::findOrFail($id);
        
        $metodoPago->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $this->middleware('permission:admin.update');
        
        try {
            $metodoPago = MetodoPago::findOrFail($id);
            $metodoPago->activo = !$metodoPago->activo;
            $metodoPago->save();
            
            return response()->json([
                'success' => 'Estado actualizado exitosamente',
                'activo' => $metodoPago->activo
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }
    }

    /**
     * Get only active payment methods
     */
    public function activos()
    {
        $response = MetodoPago::where('activo', true)
            ->orderBy('detalle', 'asc')
            ->get();

        return response()->json($response, 200);
    }
}
