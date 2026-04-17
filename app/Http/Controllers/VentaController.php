<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class VentaController extends Controller
{
    /**
     * Listar todas las ventas
     */
    public function index(Request $request)
    {
        try {
            $query = Venta::with(['usuario', 'cliente', 'detalles.producto', 'pagos.metodoPago'])
                ->orderBy('fecha_venta', 'desc');

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_venta', [$request->fecha_desde, $request->fecha_hasta]);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $ventas = $query->paginate($request->per_page ?? 15);

            return response()->json($ventas, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalle de una venta
     */
    public function show($id)
    {
        try {
            $venta = Venta::with(['usuario', 'cliente', 'detalles.producto', 'pagos.metodoPago'])
                ->findOrFail($id);

            return response()->json($venta, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Venta no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear una nueva venta
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'cliente_id' => 'nullable|exists:agd_cliente,id_cliente',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:products,id',
            'productos.*.name' => 'required|string',
            'productos.*.quantity' => 'required|integer|min:1',
            'productos.*.price' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'pagos' => 'required|array|min:1',
            'pagos.*.metodo_pago_id' => 'required|exists:agd_metodo_pago,id',
            'pagos.*.monto' => 'required|numeric|min:0',
            'pagos.*.referencia' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Calcular totales
            $subtotal = 0;
            foreach ($request->productos as $producto) {
                $subtotal += $producto['price'] * $producto['quantity'];
            }

            $descuento = $request->descuento ?? 0;
            $total = $subtotal - $descuento;

            // Calcular total pagado
            $totalPagado = collect($request->pagos)->sum('monto');

            // Validar que el monto pagado no exceda el total de la venta
            if ($totalPagado > $total) {
                return response()->json([
                    'message' => 'El monto pagado excede el total de la venta',
                    'total_venta' => $total,
                    'total_pagado' => $totalPagado
                ], 422);
            }

            // Crear la venta
            $venta = Venta::create([
                'user_id' => $request->user_id,
                'cliente_id' => $request->cliente_id,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => $totalPagado >= $total ? 'pagada' : 'pendiente',
                'observaciones' => $request->observaciones,
                'fecha_venta' => now(),
            ]);

            // Crear los detalles de la venta
            foreach ($request->productos as $producto) {
                $precioUnitario = $producto['price'];
                $cantidad = $producto['quantity'];
                $subtotalDetalle = $precioUnitario * $cantidad;

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto['id'],
                    'producto_nombre' => $producto['name'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotalDetalle,
                    'descuento' => 0,
                    'total' => $subtotalDetalle,
                ]);
            }

            // Registrar los pagos
            foreach ($request->pagos as $pago) {
                VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago_id' => $pago['metodo_pago_id'],
                    'monto' => $pago['monto'],
                    'referencia' => $pago['referencia'] ?? null,
                    'observaciones' => $pago['observaciones'] ?? null,
                    'fecha_pago' => now(),
                ]);
            }

            DB::commit();

            // Cargar las relaciones para la respuesta
            $venta->load(['usuario', 'cliente', 'detalles.producto', 'pagos.metodoPago']);

            return response()->json([
                'message' => 'Venta creada exitosamente',
                'venta' => $venta
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de una venta
     */
    public function updateEstado(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:pendiente,pagada,cancelada',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $venta = Venta::findOrFail($id);
            $venta->update(['estado' => $request->estado]);

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'venta' => $venta
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar un pago a una venta existente
     */
    public function addPago(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'metodo_pago_id' => 'required|exists:agd_metodo_pago,id',
            'monto' => 'required|numeric|min:0',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $venta = Venta::findOrFail($id);

            // Validar que la venta esté en estado pendiente
            if ($venta->estado === 'pagada') {
                return response()->json([
                    'message' => 'La venta ya está completamente pagada'
                ], 422);
            }

            if ($venta->estado === 'cancelada') {
                return response()->json([
                    'message' => 'No se pueden agregar pagos a una venta cancelada'
                ], 422);
            }

            // Calcular el total ya pagado
            $totalPagado = $venta->pagos()->sum('monto');
            $saldoPendiente = $venta->total - $totalPagado;

            // Validar que el nuevo pago no exceda el saldo pendiente
            if ($request->monto > $saldoPendiente) {
                return response()->json([
                    'message' => 'El monto del pago excede el saldo pendiente',
                    'total_venta' => $venta->total,
                    'total_pagado' => $totalPagado,
                    'saldo_pendiente' => $saldoPendiente,
                    'monto_solicitado' => $request->monto
                ], 422);
            }

            // Crear el pago
            $pago = VentaPago::create([
                'venta_id' => $venta->id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'monto' => $request->monto,
                'referencia' => $request->referencia,
                'observaciones' => $request->observaciones,
                'fecha_pago' => now(),
            ]);

            // Recalcular total pagado y actualizar estado si es necesario
            $totalPagado = $venta->pagos()->sum('monto');
            if ($totalPagado >= $venta->total && $venta->estado !== 'pagada') {
                $venta->update(['estado' => 'pagada']);
            }

            DB::commit();

            // Recargar la venta con sus relaciones
            $venta->load(['usuario', 'cliente', 'detalles.producto', 'pagos.metodoPago']);

            return response()->json([
                'message' => 'Pago agregado exitosamente',
                'pago' => $pago->load('metodoPago'),
                'total_pagado' => $totalPagado,
                'venta' => $venta
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al agregar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una venta (solo cambia estado a cancelada)
     */
    public function destroy($id)
    {
        try {
            $venta = Venta::findOrFail($id);
            
            // Solo actualizar estado a cancelada (sin soft delete)
            $venta->update(['estado' => 'cancelada']);

            return response()->json([
                'message' => 'Venta cancelada exitosamente',
                'venta' => $venta
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar la venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function estadisticas(Request $request)
    {
        try {
            $query = Venta::query();

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_venta', [$request->fecha_desde, $request->fecha_hasta]);
            }

            // Calcular monto pendiente de cobro
            $ventasPendientes = (clone $query)->where('estado', 'pendiente')->get();
            $montoPendiente = 0;
            foreach ($ventasPendientes as $venta) {
                $totalPagado = $venta->pagos()->sum('monto');
                $montoPendiente += ($venta->total - $totalPagado);
            }

            $estadisticas = [
                'total_ventas' => $query->count(),
                'ventas_pagadas' => (clone $query)->where('estado', 'pagada')->count(),
                'ventas_pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
                'ventas_canceladas' => (clone $query)->where('estado', 'cancelada')->count(),
                'monto_total' => $query->sum('total'),
                'monto_pendiente' => $montoPendiente,
            ];

            return response()->json($estadisticas, 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
