<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Venta::with(['usuario', 'detalles.producto', 'pagos.metodoPago'])
            ->orderByDesc('fecha_venta');

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_venta', [$request->fecha_desde, $request->fecha_hasta]);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function show(int $id): JsonResponse
    {
        $venta = Venta::with(['usuario', 'detalles.producto', 'pagos.metodoPago'])->findOrFail($id);

        return response()->json($venta);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_nombre' => 'nullable|string|max:255',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|exists:products,id',
            'productos.*.name' => 'required|string',
            'productos.*.quantity' => 'required|integer|min:1',
            'productos.*.price' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'pagos' => 'required|array|min:1',
            'pagos.*.metodo_pago_id' => 'required|integer|min:1',
            'pagos.*.monto' => 'required|numeric|min:0',
            'pagos.*.referencia' => 'nullable|string|max:100',
            'pagos.*.observaciones' => 'nullable|string',
        ]);

        $subtotal = collect($validated['productos'])->sum(fn ($p) => $p['price'] * $p['quantity']);
        $descuento = $validated['descuento'] ?? 0;
        $total = $subtotal - $descuento;
        $totalPagado = collect($validated['pagos'])->sum('monto');

        if ($totalPagado > $total) {
            return response()->json([
                'message' => 'El monto pagado excede el total de la venta.',
                'errors' => ['pagos' => ["El monto pagado ({$totalPagado}) excede el total ({$total})"]],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $venta = Venta::create([
                'user_id' => Auth::id(),
                'cliente_nombre' => $validated['cliente_nombre'] ?? null,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => $totalPagado >= $total ? 'pagada' : 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
                'fecha_venta' => now(),
            ]);

            foreach ($validated['productos'] as $producto) {
                $subtotalDetalle = $producto['price'] * $producto['quantity'];

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto['id'],
                    'producto_nombre' => $producto['name'],
                    'cantidad' => $producto['quantity'],
                    'precio_unitario' => $producto['price'],
                    'subtotal' => $subtotalDetalle,
                    'descuento' => 0,
                    'total' => $subtotalDetalle,
                ]);
            }

            foreach ($validated['pagos'] as $pago) {
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

            return response()->json([
                'message' => 'Venta creada exitosamente.',
                'venta' => $venta->load(['usuario', 'detalles.producto', 'pagos.metodoPago']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error al crear la venta.'], 500);
        }
    }

    public function updateEstado(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,pagada,cancelada',
        ]);

        $venta = Venta::findOrFail($id);
        $venta->update(['estado' => $validated['estado']]);

        return response()->json(['message' => 'Estado actualizado.', 'venta' => $venta]);
    }

    public function addPago(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'metodo_pago_id' => 'required|integer|min:1',
            'monto' => 'required|numeric|min:0.01',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $venta = Venta::findOrFail($id);

        if ($venta->estado === 'pagada') {
            return response()->json(['message' => 'La venta ya está completamente pagada.'], 422);
        }

        if ($venta->estado === 'cancelada') {
            return response()->json(['message' => 'No se pueden agregar pagos a una venta cancelada.'], 422);
        }

        $totalPagado = $venta->pagos()->sum('monto');
        $saldoPendiente = $venta->total - $totalPagado;

        if ($validated['monto'] > $saldoPendiente) {
            return response()->json([
                'message' => 'El monto excede el saldo pendiente.',
                'errors' => ['monto' => ["Saldo pendiente: {$saldoPendiente}"]],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $pago = VentaPago::create([
                'venta_id' => $venta->id,
                'metodo_pago_id' => $validated['metodo_pago_id'],
                'monto' => $validated['monto'],
                'referencia' => $validated['referencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'fecha_pago' => now(),
            ]);

            $totalPagado = $venta->pagos()->sum('monto');

            if ($totalPagado >= $venta->total) {
                $venta->update(['estado' => 'pagada']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pago agregado exitosamente.',
                'pago' => $pago,
                'venta' => $venta->load(['detalles.producto', 'pagos.metodoPago']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error al agregar el pago.'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $venta = Venta::findOrFail($id);
        $venta->update(['estado' => 'cancelada']);

        return response()->json(['message' => 'Venta cancelada exitosamente.', 'venta' => $venta]);
    }

    public function estadisticas(Request $request): JsonResponse
    {
        $query = Venta::query();

        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->whereBetween('fecha_venta', [$request->fecha_desde, $request->fecha_hasta]);
        }

        $montoPendiente = (clone $query)->where('estado', 'pendiente')
            ->withSum('pagos', 'monto')
            ->get()
            ->sum(fn ($v) => $v->total - ($v->pagos_sum_monto ?? 0));

        return response()->json([
            'total_ventas' => $query->count(),
            'ventas_pagadas' => (clone $query)->where('estado', 'pagada')->count(),
            'ventas_pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
            'ventas_canceladas' => (clone $query)->where('estado', 'cancelada')->count(),
            'monto_total' => (clone $query)->sum('total'),
            'monto_pendiente' => $montoPendiente,
        ]);
    }
}
