<?php

namespace App\Services;

use App\Models\TarifaRegla;
use Carbon\Carbon;

class CalculoTarifaService
{
    /**
     * Calcula el valor de una factura según la tarifa y tiempo de permanencia
     *
     * @param int $tarifaId
     * @param int $tipoVehiculoId
     * @param Carbon $entrada
     * @param Carbon $salida
     * @param int|null $valorManual
     * @return array
     * @throws \Exception
     */
    public function calcularFactura(
        int $tarifaId,
        int $tipoVehiculoId,
        Carbon $entrada,
        Carbon $salida,
        ?int $valorManual = null
    ): array {
        $minutosTotales = (int) round($entrada->diffInMinutes($salida)); //nuevo

        // 1. Si los minutos totales están en una regla FIJO o COBRO_LIBRE, usar esa regla
        $reglaMinima = TarifaRegla::where('tarifa_id', $tarifaId)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('contexto', 'TOTAL')
            ->where('minutos_desde', '<=', $minutosTotales)
            ->where('minutos_hasta', '>=', $minutosTotales)
            ->orderByDesc('prioridad')
            ->first();

        if ($reglaMinima && $reglaMinima->tipo_calculo === 'FIJO') {
            return [
                'minutos' => $minutosTotales,
                'valor' => $reglaMinima->valor,
                'regla_total_id' => $reglaMinima->id,
                'regla_fraccion_id' => null
            ];
        }
        if ($reglaMinima && $reglaMinima->tipo_calculo === 'COBRO_LIBRE') {
            if ($valorManual === null) {
                throw new \Exception('Valor manual requerido para tarifa de día libre');
            }
            return [
                'minutos' => $minutosTotales,
                'valor' => $valorManual,
                'regla_total_id' => $reglaMinima->id,
                'regla_fraccion_id' => null
            ];
        }

        // 2. Caso POR_HORA: buscar la regla POR_HORA para la hora exacta
        $horas = intdiv($minutosTotales, 60);
        $residuo = $minutosTotales % 60;
        $reglaHora = TarifaRegla::where('tarifa_id', $tarifaId)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('contexto', 'TOTAL')
            ->where('tipo_calculo', 'POR_HORA')
            ->where('minutos_desde', '<=', 60)
            ->where('minutos_hasta', '>=', 60)
            ->orderByDesc('prioridad')
            ->first();

        if (!$reglaHora) {
            throw new \Exception('No se encontró una regla de tarifa por hora para este tipo de vehículo');
        }

        $total = $horas * $reglaHora->valor;
        $reglaFraccion = null;

        // Si hay residuo, buscar regla de FRACCIÓN
        if ($residuo > 0) {
            $reglaFraccion = TarifaRegla::where('tarifa_id', $tarifaId)
                ->where('tipo_vehiculo_id', $tipoVehiculoId)
                ->where('contexto', 'FRACCION')
                ->where('minutos_desde', '<=', $residuo)
                ->where('minutos_hasta', '>=', $residuo)
                ->orderByDesc('prioridad')
                ->first();
            if ($reglaFraccion) {
                $total += $reglaFraccion->valor;
            }
        }

        return [
            'minutos' => $minutosTotales,
            'valor' => $total,
            'regla_total_id' => $reglaHora->id,
            'regla_fraccion_id' => $reglaFraccion?->id
        ];
    }
}
