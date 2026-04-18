<?php

namespace App\Http\Controllers;

use App\Models\MetodosPago;
use Illuminate\Http\Request;

class MetodosPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = MetodosPago::orderBy('detalle', 'asc')->get();

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
            'activo' => 'boolean',
        ]);

        try {
            $response = MetodosPago::create($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $response,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->middleware('permission:admin.index');
        $response = MetodosPago::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'detalle' => 'required|string|max:100|unique:metodo_pago,detalle,'.$id,
            'activo' => 'boolean',
        ]);

        try {
            $metodosPago = MetodosPago::findOrFail($id);
            $metodosPago->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json($metodosPago, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');
        $metodosPago = MetodosPago::findOrFail($id);

        $metodosPago->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $this->middleware('permission:admin.update');

        try {
            $metodosPago = MetodosPago::findOrFail($id);
            $metodosPago->activo = ! $metodosPago->activo;
            $metodosPago->save();

            return response()->json([
                'success' => 'Estado actualizado exitosamente',
                'activo' => $metodosPago->activo,
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
        $response = MetodosPago::where('activo', true)
            ->orderBy('detalle', 'asc')
            ->get();

        return response()->json($response, 200);
    }
}
