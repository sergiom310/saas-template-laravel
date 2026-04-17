<?php

namespace App\Http\Controllers;

use App\Models\PagoAgenda;
use App\Models\Gasto;
use App\Models\Profesional;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteDetalladoExport;
use App\Exports\ReporteResumidoExport;

class ReporteController extends Controller
{
    /**
     * Obtener datos del reporte con filtros
     */
    public function obtenerDatos(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            $idProfesional = $request->input('id_profesional');

            // Consultar pagos (ingresos) - solo pagados
            $pagosQuery = PagoAgenda::with(['agenda.profesional', 'agenda.cliente', 'metodoPago'])
                ->where('estado', 'pagado')
                ->whereDate('fecha_pago', '>=', $fechaInicio)
                ->whereDate('fecha_pago', '<=', $fechaFin);

            if ($idProfesional) {
                $pagosQuery->whereHas('agenda', function($q) use ($idProfesional) {
                    $q->where('id_profesional', $idProfesional);
                });
            }

            $pagos = $pagosQuery->get();

            // Consultar gastos
            $gastos = Gasto::whereBetween('fecha', [$fechaInicio, $fechaFin])->get();

            // Calcular totales
            $totalIngresos = $pagos->sum('monto');
            $totalGastos = $gastos->sum('monto');
            $balance = $totalIngresos - $totalGastos;

            // Agrupar por forma de pago
            $ingresosPorMetodo = $pagos->groupBy(function($pago) {
                return $pago->metodoPago ? $pago->metodoPago->detalle : 'Sin método';
            })->map(function($items, $metodo) {
                return [
                    'metodo' => $metodo,
                    'total' => $items->sum('monto'),
                    'cantidad' => $items->count()
                ];
            })->values();

            // Agrupar por profesional
            $ingresosPorProfesional = $pagos->groupBy(function($pago) {
                return $pago->agenda->id_profesional ?? 'sin_profesional';
            })->map(function($items) {
                $profesional = $items->first()->agenda->profesional ?? null;
                return [
                    'profesional' => $profesional ? $profesional->nombre : 'Sin profesional',
                    'total' => $items->sum('monto'),
                    'cantidad' => $items->count()
                ];
            })->values();

            // Datos para el gráfico (agrupados por día)
            $ingresosPorDia = $pagos->groupBy(function($pago) {
                return \Carbon\Carbon::parse($pago->fecha_pago)->format('Y-m-d');
            })->map(function($items, $fecha) {
                return [
                    'fecha' => $fecha,
                    'total' => $items->sum('monto')
                ];
            })->values();

            $gastosPorDia = $gastos->groupBy(function($gasto) {
                return \Carbon\Carbon::parse($gasto->fecha)->format('Y-m-d');
            })->map(function($items, $fecha) {
                return [
                    'fecha' => $fecha,
                    'total' => $items->sum('monto')
                ];
            })->values();

            $ingresosArray = $pagos->map(function($pago) {
                return [
                    'id' => $pago->id_pago,
                    'fecha' => \Carbon\Carbon::parse($pago->fecha_pago)->format('d-m-Y'),
                    'monto' => $pago->monto,
                    'metodo_pago' => $pago->metodoPago->detalle ?? 'N/A',
                    'cliente' => $pago->agenda->cliente->nombre ?? 'N/A',
                    'profesional' => $pago->agenda->profesional->nombre ?? 'N/A'
                ];
            })->toArray();

            $gastosArray = $gastos->map(function($gasto) {
                return [
                    'id' => $gasto->id,
                    'fecha' => \Carbon\Carbon::parse($gasto->fecha)->format('d-m-Y'),
                    'descripcion' => $gasto->descripcion,
                    'monto' => $gasto->monto
                ];
            })->toArray();

            return response()->json([
                'ingresos' => $ingresosArray,
                'gastos' => $gastosArray,
                'totales' => [
                    'ingresos' => $totalIngresos,
                    'gastos' => $totalGastos,
                    'balance' => $balance
                ],
                'ingresos_por_metodo' => $ingresosPorMetodo->toArray(),
                'ingresos_por_profesional' => $ingresosPorProfesional->toArray(),
                'grafico' => [
                    'ingresos_por_dia' => $ingresosPorDia->toArray(),
                    'gastos_por_dia' => $gastosPorDia->toArray()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar reporte detallado en PDF
     */
    public function pdfDetallado(Request $request)
    {
        try {
            $datos = $this->obtenerDatos($request)->getData();
            $datosArray = json_decode(json_encode($datos), true);

            $pdf = Pdf::loadView('reportes.detallado', [
                'ingresos' => $datosArray['ingresos'],
                'gastos' => $datosArray['gastos'],
                'totales' => $datosArray['totales'],
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin')
            ]);

            return $pdf->download('reporte-detallado-' . date('Y-m-d-His') . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar reporte resumido en PDF
     */
    public function pdfResumido(Request $request)
    {
        try {
            $datos = $this->obtenerDatos($request)->getData();
            $datosArray = json_decode(json_encode($datos), true);

            $pdf = Pdf::loadView('reportes.resumido', [
                'totales' => $datosArray['totales'],
                'ingresos_por_metodo' => $datosArray['ingresos_por_metodo'],
                'ingresos_por_profesional' => $datosArray['ingresos_por_profesional'],
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin')
            ]);

            return $pdf->download('reporte-resumido-' . date('Y-m-d-His') . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar reporte detallado en Excel
     */
    public function excelDetallado(Request $request)
    {
        try {
            $datos = $this->obtenerDatos($request)->getData();
            $datosArray = json_decode(json_encode($datos), true);

            return Excel::download(new ReporteDetalladoExport($datosArray), 'reporte-detallado-' . date('Y-m-d-His') . '.xlsx');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar reporte resumido en Excel
     */
    public function excelResumido(Request $request)
    {
        try {
            $datos = $this->obtenerDatos($request)->getData();
            $datosArray = json_decode(json_encode($datos), true);

            return Excel::download(new ReporteResumidoExport($datosArray), 'reporte-resumido-' . date('Y-m-d-His') . '.xlsx');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
