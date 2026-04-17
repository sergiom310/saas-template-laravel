<?php

namespace App\Http\Controllers;

use App\Models\PagoAgenda;
use App\Models\Agenda;
use App\Models\MetodoPago;
use App\Exports\PagosExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PagoAgendaController extends Controller
{
    /**
     * Listar métodos de pago activos
     */
    public function metodosPago(): JsonResponse
    {
        try {
            $metodos = MetodoPago::activos()->get();
            return response()->json($metodos, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Registrar un pago y actualizar estado de la cita
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_agenda' => 'required|exists:agd_agenda,id_agenda',
                'monto' => 'required|numeric|min:0',
                'metodo_pago' => 'required|integer|exists:agd_metodo_pago,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Crear el pago con el ID del método de pago
            $pago = PagoAgenda::create([
                'id_agenda' => $request->id_agenda,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'estado' => 'pagado',
                'fecha_pago' => now()
            ]);

            // Actualizar el estado de la cita a "atendido"
            $agenda = Agenda::findOrFail($request->id_agenda);
            $agenda->estado = 'atendido';
            $agenda->save();

            DB::commit();

            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'pago' => $pago,
                'agenda' => $agenda
            ], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Error al registrar pago', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Listar todos los pagos con información relacionada y filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PagoAgenda::with(['agenda.cliente', 'agenda.profesional', 'agenda.franjaHoraria', 'metodoPago']);

            // Filtrar por cliente
            if ($request->has('id_cliente') && $request->id_cliente) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_cliente', $request->id_cliente);
                });
            }

            // Filtrar por profesional
            if ($request->has('id_profesional') && $request->id_profesional) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_profesional', $request->id_profesional);
                });
            }

            // Filtrar por fecha de cita
            if ($request->has('fecha') && $request->fecha) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->whereDate('fecha', $request->fecha);
                });
            }

            // Filtrar por método de pago
            if ($request->has('metodo_pago') && $request->metodo_pago) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            $pagos = $query->orderBy('fecha_pago', 'desc')
                ->get()
                ->map(function ($pago) {
                    $franja = $pago->agenda->franjaHoraria;
                    $franjaTexto = $franja ? ($franja->hora_inicio . ' - ' . $franja->hora_fin) : 'N/A';

                    return [
                        'id_pago' => $pago->id_pago,
                        'id_agenda' => $pago->id_agenda,
                        'id_cliente' => $pago->agenda->id_cliente ?? null,
                        'id_profesional' => $pago->agenda->id_profesional ?? null,
                        'cliente' => $pago->agenda->cliente->nombre ?? 'N/A',
                        'profesional' => $pago->agenda->profesional->nombre ?? 'N/A',
                        'procedimiento' => $pago->agenda->procedimiento ?? 'N/A',
                        'fecha_cita' => $pago->agenda->fecha ?? null,
                        'franja_horaria' => $franjaTexto,
                        'monto' => $pago->monto,
                        'metodo_pago' => $pago->metodo_pago,
                        'metodo_pago_detalle' => $pago->metodoPago->detalle ?? 'N/A',
                        'estado' => $pago->estado,
                        'fecha_pago' => $pago->fecha_pago
                    ];
                });

            return response()->json($pagos, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Actualizar un pago
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'monto' => 'required|numeric|min:0',
                'metodo_pago' => 'required|integer|exists:agd_metodo_pago,id',
                'fecha_pago' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pago = PagoAgenda::findOrFail($id);

            $pago->monto = $request->monto;
            $pago->metodo_pago = $request->metodo_pago;
            $pago->fecha_pago = $request->fecha_pago;
            $pago->save();

            Log::info('Pago actualizado exitosamente', [
                'id_pago' => $pago->id_pago,
                'monto' => $request->monto,
                'metodo_pago_id' => $request->metodo_pago
            ]);

            return response()->json([
                'message' => 'Pago actualizado exitosamente',
                'pago' => $pago
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Error al actualizar pago', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Eliminar un pago y actualizar el estado de la cita a "reservado"
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $pago = PagoAgenda::findOrFail($id);
            $idAgenda = $pago->id_agenda;

            // Eliminar el pago
            $pago->delete();

            // Actualizar el estado de la cita a "reservado"
            $agenda = Agenda::findOrFail($idAgenda);
            $agenda->estado = 'reservado';
            $agenda->save();

            DB::commit();

            Log::info('Pago eliminado exitosamente', [
                'id_pago' => $id,
                'id_agenda' => $idAgenda
            ]);

            return response()->json([
                'message' => 'Pago eliminado exitosamente y cita actualizada a reservado'
            ], 200);

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Error al eliminar pago', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Generar reporte PDF de pagos
     */
    public function reportePDF(Request $request)
    {
        try {
            $query = PagoAgenda::with(['agenda.cliente', 'agenda.profesional', 'agenda.franjaHoraria', 'metodoPago']);

            // Aplicar filtros
            if ($request->has('id_cliente') && $request->id_cliente) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_cliente', $request->id_cliente);
                });
            }

            if ($request->has('id_profesional') && $request->id_profesional) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_profesional', $request->id_profesional);
                });
            }

            if ($request->has('fecha') && $request->fecha) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->whereDate('fecha', $request->fecha);
                });
            }

            if ($request->has('metodo_pago') && $request->metodo_pago) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            $pagos = $query->orderBy('fecha_pago', 'desc')
                ->get()
                ->map(function ($pago) {
                    $franja = $pago->agenda->franjaHoraria;
                    $franjaTexto = $franja ? ($franja->hora_inicio . ' - ' . $franja->hora_fin) : 'N/A';

                    return [
                        'id_pago' => $pago->id_pago,
                        'cliente' => $pago->agenda->cliente->nombre ?? 'N/A',
                        'profesional' => $pago->agenda->profesional->nombre ?? 'N/A',
                        'procedimiento' => $pago->agenda->procedimiento ?? 'N/A',
                        'fecha_cita' => $pago->agenda->fecha ?? null,
                        'franja_horaria' => $franjaTexto,
                        'monto' => $pago->monto,
                        'metodo_pago_detalle' => $pago->metodoPago->detalle ?? 'N/A',
                        'estado' => $pago->estado,
                        'fecha_pago' => $pago->fecha_pago
                    ];
                })->toArray();

            $pdf = Pdf::loadView('reportes.pagos', [
                'pagos' => $pagos,
                'filtros' => $request->all()
            ]);

            return $pdf->download('reporte-pagos-' . date('Y-m-d-His') . '.pdf');
        } catch (\Exception $exception) {
            Log::error('Error al generar reporte PDF', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Generar reporte Excel de pagos
     */
    public function reporteExcel(Request $request)
    {
        try {
            $query = PagoAgenda::with(['agenda.cliente', 'agenda.profesional', 'agenda.franjaHoraria', 'metodoPago']);

            // Aplicar filtros
            if ($request->has('id_cliente') && $request->id_cliente) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_cliente', $request->id_cliente);
                });
            }

            if ($request->has('id_profesional') && $request->id_profesional) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->where('id_profesional', $request->id_profesional);
                });
            }

            if ($request->has('fecha') && $request->fecha) {
                $query->whereHas('agenda', function($q) use ($request) {
                    $q->whereDate('fecha', $request->fecha);
                });
            }

            if ($request->has('metodo_pago') && $request->metodo_pago) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            $pagos = $query->orderBy('fecha_pago', 'desc')
                ->get()
                ->map(function ($pago) {
                    $franja = $pago->agenda->franjaHoraria;
                    $franjaTexto = $franja ? ($franja->hora_inicio . ' - ' . $franja->hora_fin) : 'N/A';

                    return [
                        'id_pago' => $pago->id_pago,
                        'cliente' => $pago->agenda->cliente->nombre ?? 'N/A',
                        'profesional' => $pago->agenda->profesional->nombre ?? 'N/A',
                        'procedimiento' => $pago->agenda->procedimiento ?? 'N/A',
                        'fecha_cita' => $pago->agenda->fecha ?? null,
                        'franja_horaria' => $franjaTexto,
                        'monto' => $pago->monto,
                        'metodo_pago_detalle' => $pago->metodoPago->detalle ?? 'N/A',
                        'estado' => $pago->estado,
                        'fecha_pago' => $pago->fecha_pago
                    ];
                })->toArray();

            return Excel::download(new PagosExport($pagos), 'reporte-pagos-' . date('Y-m-d-His') . '.xlsx');
        } catch (\Exception $exception) {
            Log::error('Error al generar reporte Excel', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
