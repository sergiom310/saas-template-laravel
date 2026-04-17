<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function show()
    {
        $empresa = Empresa::first();
        return response()->json($empresa);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'nit' => 'nullable|string|max:50',
            'mensaje_recibo' => 'nullable|string',
            'horario_atencion' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $empresa = Empresa::first();
        if ($empresa) {
            $empresa->update($data);
        } else {
            $empresa = Empresa::create($data);
        }
        return response()->json($empresa);
    }

    /**
     * Upload logo for the tenant.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg|max:2048', // Only JPG files, max size 2MB
        ]);

        $tenantId = auth()->user()->tenant_id; // Assuming tenant_id is stored in the user model

        // Generate a unique filename
        $filename = 'logo_' . $tenantId . '_' . \Illuminate\Support\Str::random(10) . '.jpg';

        // Store the file in a tenant-specific directory

        $path = $request->file('logo')->storeAs('tenants/' . $tenantId . '/logos', $filename, 'public');
        // Normaliza el path para evitar doble slash
        $path = ltrim(str_replace(['//', '\\'], ['/', '/'], $path), '/');

        // Save the path to the database
        $empresa = Empresa::first();
        if ($empresa && $empresa->logo_path) {
            Storage::disk('public')->delete($empresa->logo_path);
        }

        $empresa->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully.',
            'path' => $path,
        ]);
    }

    /**
     * Publicly serve the company logo stored in storage/app/public.
     * This is a fallback when the web server does not expose the /storage symlink.
     */
    public function publicLogo(Request $request)
    {
        $empresa = Empresa::first();
        if (!$empresa || !$empresa->logo_path) {
            return response()->json(['message' => 'Logo not found'], 404);
        }

        $path = storage_path('app/public/' . $empresa->logo_path);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Logo file missing'], 404);
        }

        // Leer contenido y devolver con Content-Type explícito para evitar que el servidor
        // o algún middleware cambie la respuesta a HTML.
        $contents = file_get_contents($path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        return response($contents, 200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
