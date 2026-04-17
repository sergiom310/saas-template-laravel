<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modulos = Modulo::orderBy('created_at', 'desc')->get();
        return response()->json($modulos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_modulo' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:landlord.modulos,slug',
            'descripcion' => 'nullable|string',
            'precio_mensual' => 'required|numeric|min:0',
            'precio_anual' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'nombre_modulo.required' => 'El nombre del módulo es obligatorio',
            'precio_mensual.required' => 'El precio mensual es obligatorio',
            'precio_mensual.numeric' => 'El precio mensual debe ser un número',
            'precio_mensual.min' => 'El precio mensual no puede ser negativo',
            'precio_anual.required' => 'El precio anual es obligatorio',
            'precio_anual.numeric' => 'El precio anual debe ser un número',
            'precio_anual.min' => 'El precio anual no puede ser negativo',
            'slug.unique' => 'Este slug ya está registrado',
        ]);

        // Generar slug automáticamente si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['nombre_modulo']);
        }

        // Establecer is_active en true por defecto si no se proporciona
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $modulo = Modulo::create($validated);

        return response()->json([
            'message' => 'Módulo creado exitosamente',
            'modulo' => $modulo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $modulo = Modulo::findOrFail($id);
        return response()->json($modulo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $modulo = Modulo::findOrFail($id);

        $validated = $request->validate([
            'nombre_modulo' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:landlord.modulos,slug,' . $id,
            'descripcion' => 'nullable|string',
            'precio_mensual' => 'required|numeric|min:0',
            'precio_anual' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'nombre_modulo.required' => 'El nombre del módulo es obligatorio',
            'precio_mensual.required' => 'El precio mensual es obligatorio',
            'precio_mensual.numeric' => 'El precio mensual debe ser un número',
            'precio_mensual.min' => 'El precio mensual no puede ser negativo',
            'precio_anual.required' => 'El precio anual es obligatorio',
            'precio_anual.numeric' => 'El precio anual debe ser un número',
            'precio_anual.min' => 'El precio anual no puede ser negativo',
            'slug.unique' => 'Este slug ya está registrado',
        ]);

        // Generar slug automáticamente si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['nombre_modulo']);
        }

        $modulo->update($validated);

        return response()->json([
            'message' => 'Módulo actualizado exitosamente',
            'modulo' => $modulo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $modulo = Modulo::findOrFail($id);

        // Verificar si el módulo tiene tenants asociados
        $tenantsCount = $modulo->tenants()->count();
        
        if ($tenantsCount > 0) {
            return response()->json([
                'error' => 'No se puede eliminar el módulo porque tiene ' . $tenantsCount . ' tenant(s) asociado(s)'
            ], 422);
        }

        $modulo->delete();

        return response()->json([
            'message' => 'Módulo eliminado exitosamente'
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $modulo = Modulo::findOrFail($id);
        $modulo->is_active = !$modulo->is_active;
        $modulo->save();

        return response()->json([
            'message' => 'Estado del módulo actualizado exitosamente',
            'modulo' => $modulo
        ]);
    }
}
