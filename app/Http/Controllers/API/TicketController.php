<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;
use Milon\Barcode\DNS1D;

class TicketController extends Controller
{
    /**
     * Genera el HTML del ticket de entrada para impresión térmica
     */
    public function entrada(Request $request, $facturaId)
    {
        $factura = Factura::with(['tipoVehiculo'])->findOrFail($facturaId);

        // Datos fijos de parqueadero (ajusta según tu negocio)
        $parqueadero = [
            'nombre' => 'Mi Parqueadero',
            'direccion' => 'Calle 123 #45-67',
            'nit' => '900123456-7'
        ];

        // Retornar los datos en JSON para que el frontend genere el ticket y el código de barras
        return response()->json([
            'parqueadero' => $parqueadero,
            'factura' => $factura->toArray(), // Forzar a array para incluir relaciones
        ]);
    }
}
