<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Traits\FileUploadTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    use FileUploadTrait;

    public function index(): JsonResponse
    {
        $empresa = Empresa::first();

        return response()->json($empresa, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $this->middleware('permission:admin.create');

        $request->validate([
            'nombre' => 'required|string|max:150',
            'nit' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'sitio_web' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100',
            'pais' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
            'moneda' => 'nullable|string|max:10',
            'impuesto_label' => 'nullable|string|max:30',
            'impuesto_porcentaje' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/'.$tenantId.'/empresa';
            $data = $request->except(['logo', 'imagen_header']);

            if ($request->hasFile('logo')) {
                $maxSize = (int) ini_get('upload_max_filesize') * 1000;
                $allExt = implode(',', $this->allExtensions());
                $request->validate(['logo' => 'file|mimes:'.$allExt.'|max:'.$maxSize]);
                $data['logo'] = $this->uploadFile($request->logo, $rutaArchivo);
            }

            if ($request->hasFile('imagen_header')) {
                $maxSize = (int) ini_get('upload_max_filesize') * 1000;
                $allExt = implode(',', $this->allExtensions());
                $request->validate(['imagen_header' => 'file|mimes:'.$allExt.'|max:'.$maxSize]);
                $data['imagen_header'] = $this->uploadFile($request->imagen_header, $rutaArchivo);
            }

            $empresa = Empresa::create($data);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json($empresa, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->middleware('permission:admin.update');

        $request->validate([
            'nombre' => 'required|string|max:150',
            'nit' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'sitio_web' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100',
            'pais' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
            'moneda' => 'nullable|string|max:10',
            'impuesto_label' => 'nullable|string|max:30',
            'impuesto_porcentaje' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $empresa = Empresa::findOrFail($id);
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/'.$tenantId.'/empresa';
            $data = $request->except(['logo', 'imagen_header']);

            if ($request->hasFile('logo')) {
                $maxSize = (int) ini_get('upload_max_filesize') * 1000;
                $allExt = implode(',', $this->allExtensions());
                $request->validate(['logo' => 'file|mimes:'.$allExt.'|max:'.$maxSize]);
                if ($empresa->logo) {
                    $this->deleteFile(str_replace('storage/', 'public/', $empresa->logo));
                }
                $data['logo'] = $this->uploadFile($request->logo, $rutaArchivo);
            }

            if ($request->hasFile('imagen_header')) {
                $maxSize = (int) ini_get('upload_max_filesize') * 1000;
                $allExt = implode(',', $this->allExtensions());
                $request->validate(['imagen_header' => 'file|mimes:'.$allExt.'|max:'.$maxSize]);
                if ($empresa->imagen_header) {
                    $this->deleteFile(str_replace('storage/', 'public/', $empresa->imagen_header));
                }
                $data['imagen_header'] = $this->uploadFile($request->imagen_header, $rutaArchivo);
            }

            $empresa->update($data);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json($empresa, 200);
    }
}
