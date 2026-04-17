<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $response = Pago::with(['cliente', 'metodo_pago'])->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cod_cli' => 'required|exists:cliente,cod_cli',
            'val_pag' => 'nullable|numeric',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d',
            'fecha_pag' => 'nullable|date_format:Y-m-d',
            'horap' => 'nullable|string|max:5',
            'cod_forp' => 'nullable|integer|exists:metodo_pago,id',
            // Campos adicionales para mensualidad
            'periodo' => 'nullable|string|max:7',
            'id_cuadre' => 'nullable|integer|exists:cuadre_caja,id_cuadre',
        ]);

        try {
            // Si es pago de mensualidad, exigir que exista un cuadre abierto para el usuario autenticado
            if (!empty($validated['periodo'])) {
                $idCuadre = $validated['id_cuadre'] ?? null;
                if ($idCuadre === null) {
                    $userName = Auth::user() ? Auth::user()->email : null;
                    $cuadreAbierto = \DB::table('cuadre_caja')->where('usuario', $userName)->where('estado', 'ABIERTO')->first();
                    $idCuadre = $cuadreAbierto->id_cuadre ?? null;
                }

                if ($idCuadre === null) {
                    return response()->json(['error' => 'No hay cuadre de caja abierto para este usuario'], 422);
                }
            }

            $data = $validated;
            $data['user_sys'] = Auth::user() ? Auth::user()->email : null;
            $response = Pago::create($data);
            $response->load(['cliente', 'metodo_pago']);

            // Si el payload indica un pago de mensualidad (periodo presente), insertar también en pago_mensualidad
            if (!empty($validated['periodo'])) {
                // requerir id_cuadre para registrar en pago_mensualidad
                $idCuadre = $validated['id_cuadre'] ?? null;
                if ($idCuadre === null) {
                    // si no se proporciona id_cuadre, intentar buscar un cuadre abierto para el usuario
                    $userName = Auth::user() ? Auth::user()->email : null;
                    $cuadreAbierto = \DB::table('cuadre_caja')->where('usuario', $userName)->where('estado', 'ABIERTO')->first();
                    $idCuadre = $cuadreAbierto->id_cuadre ?? null;
                }

                // Insertar solo si tenemos id_cuadre válido
                if ($idCuadre) {
                    \DB::table('pago_mensualidad')->insert([
                        'id_cliente' => $validated['cod_cli'],
                        'id_cuadre' => $idCuadre,
                        'metodo_pago_id' => $validated['cod_forp'] ?? 0,
                        'periodo' => $validated['periodo'],
                        'fecha_pago' => $validated['fecha_pag'] ?? now(),
                        'valor' => $validated['val_pag'] ?? 0,
                        'usuario' => $data['user_sys'] ?? (Auth::user()->email ?? 'system'),
                    ]);
                }
            }
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
        $response = Pago::with(['cliente', 'metodo_pago'])->whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cod_cli' => 'required|exists:cliente,cod_cli',
            'val_pag' => 'nullable|numeric',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d',
            'fecha_pag' => 'nullable|date_format:Y-m-d',
            'horap' => 'nullable|string|max:5',
            'cod_forp' => 'nullable|integer|exists:metodo_pago,id',
        ]);

        try {
            $pago = Pago::findOrFail($id);
            $data = $validated;
            $data['user_sys'] = Auth::user() ? Auth::user()->email : null;
            $pago->update($data);
            $pago->load(['cliente', 'metodo_pago']);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente', 'data' => $pago], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');

        try {
            $pago = Pago::findOrFail($id);
            // Guardar datos para eliminar coincidencias en pago_mensualidad
            $codCli = $pago->cod_cli;
            $fecha = $pago->fecha_pag;
            $valor = $pago->val_pag;
            $usuario = $pago->user_sys;

            $pago->delete();

            // Eliminar registros en pago_mensualidad que coincidan (cliente, fecha, valor, usuario)
            try {
                \DB::table('pago_mensualidad')
                    ->where('id_cliente', $codCli)
                    ->where('fecha_pago', $fecha)
                    ->where('valor', $valor)
                    ->where('usuario', $usuario)
                    ->delete();
            } catch (\Exception $e) {
                // No bloquear la operación principal si falla la eliminación secundaria
            }
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Get all payments for a specific client
     */
    public function pagosPorCliente($codCli)
    {
        $this->middleware('permission:admin.index');

        try {
            $pagos = Pago::where('cod_cli', $codCli)
                ->with(['cliente', 'metodo_pago'])
                ->get();

            return response()->json($pagos, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
}
