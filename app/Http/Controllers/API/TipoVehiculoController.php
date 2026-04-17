<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoVehiculo;
use Illuminate\Http\Request;

class TipoVehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = TipoVehiculo::get();

        return response()->json($response, 200);
    }
    
    public function registrosActivos()
    {
        $this->middleware('permission:admin.index');
        $response = TipoVehiculo::where('status', 'Activo')->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'nombre' => 'required|string|max:50',
            'imagen' => 'nullable|string|max:255',
            'etiqueta_detalle' => 'nullable|string|max:200',
        ]);

        try {
            $response = TipoVehiculo::create($request->all());
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
        $response = TipoVehiculo::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'nombre' => 'required|string|max:50',
            'imagen' => 'nullable|string|max:255',
            'etiqueta_detalle' => 'nullable|string|max:200',
        ]);

        try {
            $tipoVehiculo = TipoVehiculo::findOrFail($id);
            $tipoVehiculo->update($request->all());
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
        $tipoVehiculo = TipoVehiculo::findOrFail($id);
        
        $tipoVehiculo->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }
    
    /**
     * Activate the category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        $this->middleware('permission:admin.update');
        $tipoVehiculo = TipoVehiculo::findOrFail($id);

        try {
            $tipoVehiculo->update([
                'status' => $request['status']
            ]);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado'], 200);
    }

}
