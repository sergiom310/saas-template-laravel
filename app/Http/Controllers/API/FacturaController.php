<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\CuadreCaja;
use App\Services\CalculoTarifaService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * Confirmar cobro: registrar salida, calcular valor y guardar pago en una sola transacción
     */
    public function confirmarCobro(Request $request, $facturaId)
    {
        $factura = Factura::findOrFail($facturaId);

        if ($factura->estado === 'CERRADA') {
            return response()->json(['error' => 'Factura ya cerrada'], 400);
        }

        $request->validate([
            'valor_pagado' => 'required|numeric|min:0',
            'metodo_pago_id' => 'required|exists:metodo_pago,id'
        ]);

        $salida = now();

        try {
            $valorManual = $request->input('valor_manual');
            $resultado = null;
            if ($valorManual !== null) {
                // Si el usuario digitó un valor manual, ignorar la validación de regla de tarifa
                $minutos = (int) round(Carbon::parse($factura->fecha_entrada)->diffInMinutes($salida));
                $resultado = [
                    'minutos' => $minutos,
                    'valor' => 0, // valor_calculado será 0
                    'regla_total_id' => null,
                    'regla_fraccion_id' => null
                ];
                $valorPagado = $valorManual;
                $valorTotal = $valorManual;
            } else {
                // Si no hay valor manual, proceder con la lógica normal
                $resultado = app(CalculoTarifaService::class)
                    ->calcularFactura(
                        $factura->tarifa_id,
                        $factura->tipo_vehiculo_id,
                        Carbon::parse($factura->fecha_entrada),
                        $salida,
                        null
                    );
                $valorPagado = $request->input('valor_pagado');
                $valorTotal = $factura->valor_manual ?? $resultado['valor'];
            }
            $pendiente = max(0, $valorTotal - $valorPagado);

            $factura->update([
                'fecha_salida' => $salida,
                'minutos_total' => $resultado['minutos'],
                'valor_calculado' => $resultado['valor'], // 0 si es manual
                'valor_manual' => $valorManual,
                'regla_total_id' => $resultado['regla_total_id'],
                'regla_fraccion_id' => $resultado['regla_fraccion_id'],
                'valor_pagado' => $valorPagado,
                'pendiente' => $pendiente,
                'pendiente_flag' => $pendiente > 0 ? 'S' : 'N',
                'metodo_pago_id' => $request->input('metodo_pago_id'),
                'estado' => 'CERRADA',
                'user_updated' => auth()->id()
            ]);

            // Registrar en pago_parqueadero asociando al cuadre abierto del usuario
            $user = $request->user();
            $username = $user->email ?? null;
            $cuadreAbierto = CuadreCaja::where('usuario', $username)->where('estado', 'ABIERTO')->first();
            if (!$cuadreAbierto) {
                return response()->json(['error' => 'No hay cuadre de caja abierto para este usuario'], 422);
            }

            try {
                DB::table('pago_parqueadero')->insert([
                    'id_factura' => $factura->id,
                    'id_cuadre' => $cuadreAbierto->id_cuadre,
                    'metodo_pago_id' => $request->input('metodo_pago_id'),
                    'valor' => $valorPagado,
                    'usuario' => $username,
                    // 'fecha_pago' se llena por defecto en la BD
                ]);
            } catch (\Exception $e) {
                // Si falla el registro en pago_parqueadero, revertir o notificar
                // Retornar error para que el frontend lo maneje
                return response()->json(['error' => 'No se pudo registrar el pago en pago_parqueadero: ' . $e->getMessage()], 422);
            }

            return response()->json([
                'mensaje' => 'Cobro confirmado exitosamente',
                'factura' => $factura->fresh(['tarifa', 'tipoVehiculo', 'metodoPago', 'reglaTotal', 'reglaFraccion']),
                'total_a_pagar' => $valorTotal,
                'valor_pagado' => $valorPagado,
                'pendiente' => $pendiente
            ], 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
    /**
     * Calcula el valor de la tarifa según los parámetros enviados (sin cerrar factura)
     */
    public function calcularTarifa(Request $request)
    {
        $request->validate([
            'tarifa_id' => 'required|exists:tarifa,id',
            'tipo_vehiculo_id' => 'required|exists:tipo_vehiculo,id',
            'fecha_entrada' => 'required|date',
            'fecha_salida' => 'required|date|after_or_equal:fecha_entrada',
            'valor_manual' => 'nullable|integer|min:0'
        ]);

        try {
            $resultado = app(CalculoTarifaService::class)
                ->calcularFactura(
                    $request->input('tarifa_id'),
                    $request->input('tipo_vehiculo_id'),
                    Carbon::parse($request->input('fecha_entrada')),
                    Carbon::parse($request->input('fecha_salida')),
                    $request->input('valor_manual')
                );

            return response()->json([
                'valor' => $resultado['valor'],
                'minutos' => $resultado['minutos'],
                'regla_total_id' => $resultado['regla_total_id'],
                'regla_fraccion_id' => $resultado['regla_fraccion_id'] ?? null
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //Log::info('Filtros recibidos en facturas:', $request->all());
        $this->middleware('permission:admin.index');
        $response = Factura::with(['tarifa', 'tipoVehiculo', 'metodoPago', 'userCreator'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'tarifa_id' => 'nullable|exists:tarifa,id',
            'tipo_vehiculo_id' => 'required|exists:tipo_vehiculo,id',
            'placa' => 'required|string|max:20',
            // 'fecha_entrada' => 'required|date', // No se valida ni se espera del frontend
            'metodo_pago_id' => 'nullable|exists:metodo_pago,id',
            'observacion' => 'nullable|string'
        ]);

        try {
            $data = $request->except('fecha_entrada'); // Ignorar si viene del frontend
            $data['user_created'] = auth()->id();
            $data['estado'] = 'ABIERTA';
            $data['fecha_entrada'] = now(); // Asignar fecha y hora actual del backend

            $response = Factura::create($data);
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
        $response = Factura::with([
            'tarifa',
            'tipoVehiculo',
            'metodoPago',
            'reglaTotal',
            'reglaFraccion',
            'userCreator',
            'userUpdater'
        ])->whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'id' => 'required|exists:factura,id',
            'metodo_pago_id' => 'nullable|exists:metodo_pago,id',
            'observacion' => 'nullable|string',
            'detalle' => 'nullable|string|max:255'
        ]);

        try {
            $factura = Factura::findOrFail($request->id);

            if ($factura->estado === 'CERRADA') {
                return response()->json(['error' => 'No se puede modificar una factura cerrada'], 422);
            }

            $data = $request->all();
            $data['user_updated'] = auth()->id();

            $factura->update($data);
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
        $factura = Factura::findOrFail($id);

        if ($factura->estado === 'CERRADA') {
            return response()->json(['error' => 'No se puede eliminar una factura cerrada'], 422);
        }

        $factura->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Registrar la salida del vehículo y calcular el valor a pagar
     */
    public function registrarSalida(Request $request, $facturaId)
    {
        $factura = Factura::findOrFail($facturaId);

        if ($factura->estado === 'CERRADA') {
            return response()->json(['error' => 'Factura ya cerrada'], 400);
        }

        $salida = now();

        try {
            $resultado = app(CalculoTarifaService::class)
                ->calcularFactura(
                    $factura->tarifa_id,
                    $factura->tipo_vehiculo_id,
                    Carbon::parse($factura->fecha_entrada),
                    $salida,
                    $request->input('valor_manual')
                );

            $factura->update([
                'fecha_salida' => $salida,
                'minutos_total' => $resultado['minutos'],
                'valor_calculado' => $resultado['valor'],
                'valor_manual' => $request->input('valor_manual'),
                'regla_total_id' => $resultado['regla_total_id'],
                'regla_fraccion_id' => $resultado['regla_fraccion_id'],
                'estado' => 'CERRADA',
                'user_updated' => auth()->id()
            ]);

            return response()->json([
                'mensaje' => 'Salida registrada exitosamente',
                'factura' => $factura->fresh(['tarifa', 'tipoVehiculo', 'reglaTotal', 'reglaFraccion']),
                'total_a_pagar' => $resultado['valor'],
                'minutos_permanencia' => $resultado['minutos']
            ], 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }

    /**
     * Registrar el pago de una factura
     */
    public function registrarPago(Request $request, $facturaId)
    {
        $factura = Factura::findOrFail($facturaId);

        $request->validate([
            'valor_pagado' => 'required|numeric|min:0',
            'metodo_pago_id' => 'required|exists:metodo_pago,id'
        ]);

        try {
            $valorPagado = $request->input('valor_pagado');
            $valorTotal = $factura->valor_manual ?? $factura->valor_calculado;
            $pendiente = max(0, $valorTotal - $valorPagado);

            $factura->update([
                'valor_pagado' => $valorPagado,
                'pendiente' => $pendiente,
                'pendiente_flag' => $pendiente > 0 ? 'S' : 'N',
                'metodo_pago_id' => $request->input('metodo_pago_id'),
                'user_updated' => auth()->id()
            ]);

            return response()->json([
                'mensaje' => 'Pago registrado exitosamente',
                'factura' => $factura->fresh(['metodoPago']),
                'pendiente' => $pendiente
            ], 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }
}
