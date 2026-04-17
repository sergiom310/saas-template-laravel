<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuracion;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $config = Configuracion::first();
        return response()->json($config);
    }

    public function update(Request $request)
    {
        $config = Configuracion::first();
        if (!$config) {
            $config = new Configuracion();
        }
        $config->imp_logo = $request->input('imp_logo', 'S');
        $config->imp_mensaje = $request->input('imp_mensaje', 'S');
        $config->imp_nit = $request->input('imp_nit', 'S');
        $config->imp_tel = $request->input('imp_tel', 'S');
        $config->imp_dir = $request->input('imp_dir', 'S');
        $config->save();
        return response()->json(['success' => true, 'config' => $config]);
    }
}
