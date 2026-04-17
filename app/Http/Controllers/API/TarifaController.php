<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tarifa;
use Illuminate\Http\Request;

class TarifaController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos en el constructor
        // Comentado temporalmente para debug
        // $this->middleware('permission:admin.index')->only(['index', 'show']);
        // $this->middleware('permission:admin.create')->only(['store']);
        // $this->middleware('permission:admin.update')->only(['update', 'activate']);
        // $this->middleware('permission:admin.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $response = Tarifa::all();
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'status' => 'string|in:Activo,Inactivo'
        ]);

        try {
            $response = Tarifa::create($request->all());
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
        try {
            $response = Tarifa::findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'status' => 'string|in:Activo,Inactivo'
        ]);

        try {
            $tarifa = Tarifa::findOrFail($id);
            $tarifa->update($request->all());
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
        try {
            $tarifa = Tarifa::findOrFail($id);
            $tarifa->delete();
            return response()->json(['success' => 'Registro eliminado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }

    /**
     * Activate the category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        try {
            $tarifa = Tarifa::findOrFail($id);
            $tarifa->update([
                'status' => $request['status']
            ]);
            return response()->json(['success' => 'Registro actualizado'], 200);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }
    }

}
