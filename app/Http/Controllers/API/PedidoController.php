<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Admin: listar todos los pedidos con usuario y detalles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'details.product'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('status_payment')) {
            $query->where('status_payment', $request->status_payment);
        }

        return response()->json($query->get());
    }

    /**
     * Cliente: crear un pedido desde el carrito.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:products,id',
            'productos.*.quantity' => 'required|integer|min:1',
            'productos.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            $subtotal = collect($request->productos)->sum(
                fn ($p) => $p['price'] * $p['quantity']
            );
            $discount = $request->discount ?? 0;
            $total = $subtotal - $discount;

            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'Pendiente',
                'status_payment' => 'pendiente',
                'total_payment' => $total,
                'discount' => $discount,
                'balance_payment' => $total,
                'notes' => $request->notes,
            ]);

            foreach ($request->productos as $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json($order->load(['user', 'details.product']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear pedido: '.$e->getMessage());

            return response()->json(['error' => 'Error al crear el pedido'], 500);
        }
    }

    /**
     * Detalle de un pedido.
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['user', 'details.product'])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Admin: cambiar estado del pedido y opcionalmente registrar pago.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Pendiente,Procesando,Completado,Cancelado',
            'status_payment' => 'nullable|in:pendiente,pagado',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;

        if ($request->filled('status_payment')) {
            $order->status_payment = $request->status_payment;

            if ($request->status_payment === 'pagado') {
                $order->date_payment = now();
                $order->balance_payment = 0;
            }
        }

        if ($request->filled('notes')) {
            $order->notes = $request->notes;
        }

        $order->save();

        return response()->json($order->load(['user', 'details.product']));
    }

    /**
     * Cliente: listar sus propios pedidos.
     */
    public function misOrdenes(): JsonResponse
    {
        $orders = Order::with(['details.product'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Admin: estadísticas de pedidos.
     */
    public function estadisticas(): JsonResponse
    {
        return response()->json([
            'total' => Order::count(),
            'pendientes' => Order::where('status', 'Pendiente')->count(),
            'procesando' => Order::where('status', 'Procesando')->count(),
            'completados' => Order::where('status', 'Completado')->count(),
            'cancelados' => Order::where('status', 'Cancelado')->count(),
            'por_cobrar' => Order::where('status_payment', 'pendiente')
                ->whereNotIn('status', ['Cancelado'])
                ->sum('balance_payment'),
        ]);
    }
}
