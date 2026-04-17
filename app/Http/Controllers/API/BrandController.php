<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Bitacora;
use Carbon\Carbon;
use App\Traits\FileUploadTrait;

class BrandController extends Controller
{
    use FileUploadTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $Brands = Brand::get();

        return response()->json($Brands, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');

        try {
            $max_size = (int)ini_get('upload_max_filesize') * 1000;
            $all_ext = implode(',', $this->allExtensions());
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/' . $tenantId . '/brands';
            if($request->hasFile('image')) {
                $file = $request->image;
                $ext = $file->getClientOriginalExtension();
                $request->validate([
                    'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $path = $this->uploadFile($file, $rutaArchivo);
                // Guardar la ruta en formato storage/tenants/{tenantId}/brands/xxx.jpg para usar storage:link
                $request['imagen'] = str_replace('public/', 'storage/', $path);
            }
            $response = Brand::create($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
        return response()->json([
            "data" => $response
        ], 200);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->middleware('permission:admin.index');
        $response = Brand::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');
        $Brand = Brand::findOrFail($id);

        $max_size = (int)ini_get('upload_max_filesize') * 1000;
        $all_ext = implode(',', $this->allExtensions());
        $tenantId = optional(app('currentTenant'))->id ?? 'global';
        $rutaArchivo = 'public/tenants/' . $tenantId . '/brands';

        $data = $request->except(['image']);

        if($request->hasFile('image')) {
            $file = $request->image;
            $ext = $file->getClientOriginalExtension();
            $request->validate([
                'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
            ]);

            // Eliminar imagen anterior si existe
            if ($Brand->imagen) {
                $rutaImagen = str_replace('storage/', 'public/', $Brand->imagen);
                $this->deleteFile($rutaImagen);
            }

            $path = $this->uploadFile($file, $rutaArchivo);
            // Guardar la ruta en formato storage/tenants/{tenantId}/brands/xxx.jpg para usar storage:link
            $data['imagen'] = str_replace('public/', 'storage/', $path);
        }

        try {
            $Brand->update($data);
        } catch (\Exception $exception) {
            \Log::error('Brand update error', ['error' => $exception->getMessage()]);
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');
        $Brand = Brand::findOrFail($id);
        // Eliminar imagen del tenant si existe
        if ($Brand->imagen) {
            // Si la ruta es tipo storage/tenants/{tenantId}/brands/xxx.jpg
            $rutaImagen = str_replace('storage/', 'public/', $Brand->imagen);
            $this->deleteFile($rutaImagen);
        }
        $Brand->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Activate the category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        $this->middleware('permission:admin.update');
        $Brand = Brand::findOrFail($id);

        try {
            $Brand->update([
                'status' => $request['status']
            ]);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado'], 200);
    }

}
