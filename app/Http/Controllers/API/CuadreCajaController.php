<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Models\CuadreCaja;
use Carbon\Carbon;

class CuadreCajaController extends Controller
{
    public function index()
    {
        $cuadres = CuadreCaja::orderBy('fecha_apertura', 'desc')->get()->toArray();

        // attach resumen totals for each cuadre (parqueadero, mensualidades, total)
        foreach ($cuadres as &$c) {
            $ap = $c['fecha_apertura'];
            $ci = $c['fecha_cierre'] ?? Carbon::now()->toDateTimeString();

            // Preferir sumar por id_cuadre (si los pagos fueron asociados), sino usar rango de fechas
            $idCuadre = $c['id_cuadre'];
            $parque = DB::table('pago_parqueadero')
                ->where('id_cuadre', $idCuadre)
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
            if ((float)$parque == 0) {
                $parque = DB::table('pago_parqueadero')
                    ->whereBetween('fecha_pago', [$ap, $ci])
                    ->selectRaw('COALESCE(SUM(valor),0) as total')
                    ->value('total');
            }

            $mens = DB::table('pago_mensualidad')
                ->where('id_cuadre', $idCuadre)
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
            if ((float)$mens == 0) {
                $mens = DB::table('pago_mensualidad')
                    ->whereBetween('fecha_pago', [$ap, $ci])
                    ->selectRaw('COALESCE(SUM(valor),0) as total')
                    ->value('total');
            }

            $c['resumen'] = [
                'parqueadero' => (float)$parque,
                'mensualidades' => (float)$mens,
                'total' => (float)($parque + $mens),
            ];
        }

        return response()->json($cuadres);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $username = $user->email ?? 'usuario';

        // check if user already has an open cuadre
        $open = CuadreCaja::where('usuario', $username)->where('estado', 'ABIERTO')->first();
        if ($open) {
            return response()->json(['message' => 'Ya existe un cuadre abierto para este usuario'], 422);
        }

        $base = $request->input('base', 0);

        $cuadre = CuadreCaja::create([
            'usuario' => $username,
            'fecha_apertura' => Carbon::now()->toDateTimeString(),
            'estado' => 'ABIERTO',
            'total_ingresos' => 0,
            'base' => (float)$base,
        ]);

        return response()->json($cuadre);
    }

    public function close(Request $request, $id)
    {
        $cuadre = CuadreCaja::find($id);
        if (!$cuadre) return response()->json(['message' => 'Cuadre no encontrado'], 404);
        if ($cuadre->estado !== 'ABIERTO') return response()->json(['message' => 'Cuadre ya cerrado'], 422);

        $ap = $cuadre->fecha_apertura;
        $ci = Carbon::now()->toDateTimeString();

        $idCuadre = $cuadre->id_cuadre;

        // Totales generales
        $parque = DB::table('pago_parqueadero')
            ->where('id_cuadre', $idCuadre)
            ->selectRaw('COALESCE(SUM(valor),0) as total')
            ->value('total');
        if ((float)$parque == 0) {
            $parque = DB::table('pago_parqueadero')
                ->whereBetween('fecha_pago', [$ap, $ci])
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
        }

        $mens = DB::table('pago_mensualidad')
            ->where('id_cuadre', $idCuadre)
            ->selectRaw('COALESCE(SUM(valor),0) as total')
            ->value('total');
        if ((float)$mens == 0) {
            $mens = DB::table('pago_mensualidad')
                ->whereBetween('fecha_pago', [$ap, $ci])
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
        }

        $total = (float)$parque + (float)$mens;

        // Preparar map de totales por metodo
        $map = [];

        $parqueByMethod = DB::table('pago_parqueadero')
            ->select('metodo_pago_id', DB::raw('COALESCE(SUM(valor),0) as total'))
            ->where('id_cuadre', $idCuadre)
            ->orWhereBetween('fecha_pago', [$ap, $ci])
            ->groupBy('metodo_pago_id')
            ->get();

        foreach ($parqueByMethod as $row) {
            $mid = $row->metodo_pago_id ?: 0;
            $map[$mid] = ($map[$mid] ?? 0) + (float)$row->total;
        }

        $mensByMethod = DB::table('pago_mensualidad')
            ->select('metodo_pago_id', DB::raw('COALESCE(SUM(valor),0) as total'))
            ->where('id_cuadre', $idCuadre)
            ->orWhereBetween('fecha_pago', [$ap, $ci])
            ->groupBy('metodo_pago_id')
            ->get();

        foreach ($mensByMethod as $row) {
            $mid = $row->metodo_pago_id ?: 0;
            $map[$mid] = ($map[$mid] ?? 0) + (float)$row->total;
        }

        // Borrar y reinsertar detalles
        DB::table('cuadre_detalle')->where('id_cuadre', $idCuadre)->delete();

        $inserts = [];
        foreach ($map as $metodo => $mTotal) {
            if ((float)$mTotal == 0) continue;
            $inserts[] = [
                'id_cuadre' => $idCuadre,
                'metodo_pago_id' => (int)$metodo,
                'total' => number_format($mTotal, 2, '.', ''),
            ];
        }

        if (!empty($inserts)) {
            DB::table('cuadre_detalle')->insert($inserts);
        }

        // Guardar cierre
        $cuadre->fecha_cierre = $ci;
        $cuadre->total_ingresos = $total;
        $cuadre->estado = 'CERRADO';

        try {
            $cuadre->save();
        } catch (QueryException $e) {
            // Detectar violación por índice único conocido y tratar de corregirlo temporalmente
            $msg = $e->getMessage();
            if (str_contains($msg, 'ux_cuadre_usuario_abierto')) {
                try {
                    Schema::table('cuadre_caja', function ($table) {
                        $table->dropUnique('ux_cuadre_usuario_abierto');
                    });
                } catch (\Exception $ex) {
                    // si falla al eliminar el índice, devolver error claro
                    return response()->json(['message' => 'Error al cerrar cuadre: conflicto de índice único. Ejecuta las migraciones para corregir el esquema.'], 500);
                }

                // Reintentar guardar
                try {
                    $cuadre->save();
                } catch (\Exception $e2) {
                    return response()->json(['message' => 'Error al cerrar cuadre tras intentar corregir índice. Por favor revisa los logs.'], 500);
                }
            } else {
                // otra excepción de consulta
                return response()->json(['message' => 'Error al cerrar cuadre.'], 500);
            }
        }

        return response()->json(['parqueadero' => (float)$parque, 'mensualidades' => (float)$mens, 'total' => $total]);
    }

    public function resumen($id)
    {
        $cuadre = CuadreCaja::find($id);
        if (!$cuadre) return response()->json(['message' => 'Cuadre no encontrado'], 404);

        $ap = $cuadre->fecha_apertura;
        $ci = $cuadre->fecha_cierre ?? Carbon::now()->toDateTimeString();

        $parque = DB::table('pago_parqueadero')
            ->where('id_cuadre', $id)
            ->selectRaw('COALESCE(SUM(valor),0) as total')
            ->value('total');
        if ((float)$parque == 0) {
            $parque = DB::table('pago_parqueadero')
                ->whereBetween('fecha_pago', [$ap, $ci])
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
        }

        $mens = DB::table('pago_mensualidad')
            ->where('id_cuadre', $id)
            ->selectRaw('COALESCE(SUM(valor),0) as total')
            ->value('total');
        if ((float)$mens == 0) {
            $mens = DB::table('pago_mensualidad')
                ->whereBetween('fecha_pago', [$ap, $ci])
                ->selectRaw('COALESCE(SUM(valor),0) as total')
                ->value('total');
        }

        return response()->json(['parqueadero' => (float)$parque, 'mensualidades' => (float)$mens, 'total' => (float)($parque + $mens)]);
    }

    /**
     * Detalle de un cuadre: incluye pagos de parqueadero y mensualidades
     */
    public function detalle($id)
    {
        $cuadre = CuadreCaja::find($id);
        if (!$cuadre) return response()->json(['message' => 'Cuadre no encontrado'], 404);

        $ap = $cuadre->fecha_apertura;
        $ci = $cuadre->fecha_cierre ?? Carbon::now()->toDateTimeString();

        $parque = DB::table('pago_parqueadero as pp')
            ->leftJoin('metodo_pago as mp', 'pp.metodo_pago_id', '=', 'mp.id')
            ->select('pp.id_pago','pp.id_factura','pp.fecha_pago','pp.valor','pp.usuario','mp.detalle as metodo')
            ->where('pp.id_cuadre', $id)
            ->orWhereBetween('pp.fecha_pago', [$ap, $ci])
            ->orderBy('pp.fecha_pago','asc')
            ->get();

        $mens = DB::table('pago_mensualidad as pm')
            ->leftJoin('cliente as c', 'pm.id_cliente', '=', 'c.cod_cli')
            ->leftJoin('metodo_pago as mp', 'pm.metodo_pago_id', '=', 'mp.id')
            ->select('pm.id_pago','pm.id_cliente','c.nom_cli as cliente','pm.periodo','pm.fecha_pago','pm.valor','pm.usuario','mp.detalle as metodo')
            ->where('pm.id_cuadre', $id)
            ->orWhereBetween('pm.fecha_pago', [$ap, $ci])
            ->orderBy('pm.fecha_pago','asc')
            ->get();

        $resumen = [
            'parqueadero' => (float) DB::table('pago_parqueadero')->where('id_cuadre', $id)->selectRaw('COALESCE(SUM(valor),0) as total')->value('total'),
            'mensualidades' => (float) DB::table('pago_mensualidad')->where('id_cuadre', $id)->selectRaw('COALESCE(SUM(valor),0) as total')->value('total'),
        ];
        $resumen['total'] = $resumen['parqueadero'] + $resumen['mensualidades'];

        return response()->json([
            'cuadre' => $cuadre,
            'resumen' => $resumen,
            'pagos_parqueadero' => $parque,
            'pagos_mensualidad' => $mens,
        ]);
    }
}
