<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\API\CategoryRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\Bitacora;
use Carbon\Carbon;
use App\Traits\FileUploadTrait;

class CategoryController extends Controller
{
    use FileUploadTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $selectRaw = "categories.id,categories.parent_id,categories.status,categories.icono,categories.description,categories.banner,categories.imagen,c2.name as name2,categories.orden,categories.name,categories.name_en,categories.description_en,categories.slug,categories.created_at,categories.updated_at";
        if ($page = \Request::get('page')) {
            $limit = \Request::get('limit') ? \Request::get('limit') : 20;
            $Category = Category::selectRaw($selectRaw)->leftJoin('categories as c2','categories.parent_id','=','c2.id')->paginate($limit);
        } else {
            $Category = Category::selectRaw($selectRaw)->leftJoin('categories as c2','categories.parent_id','=','c2.id')->get();
        }

        return response()->json($Category, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        $this->middleware('permission:admin.create');

        try {
            $max_size = (int)ini_get('upload_max_filesize') * 1000;
            $all_ext = implode(',', $this->allExtensions());
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/' . $tenantId . '/category_photos';

            $data = $request->except(['image', 'banner']);

            if($request->hasFile('image')) {
                $file = $request->image;
                $ext = $file->getClientOriginalExtension();
                $request->validate([
                    'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $path = $this->uploadFile($file, $rutaArchivo);
                $data['imagen'] = $path;
            }
            if($request->hasFile('banner')) {
                $file = $request->banner;
                $ext = $file->getClientOriginalExtension();
                $request->validate([
                    'banner' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $path = $this->uploadFile($file, $rutaArchivo);
                $data['banner'] = $path;
            }

            $data['name_en'] = $request['name_en'] === 'null' || $request['name_en'] === '' ? null : $request['name_en'];
            $data['description_en'] = $request['description_en'] === 'null' || $request['description_en'] === '' ? null : $request['description_en'];

            $response = Category::create($data);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->middleware('permission:admin.index');
        $response = Category::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, $id)
    {
        $this->middleware('permission:admin.update');

        try {
            $Category = Category::findOrFail($id);
            $max_size = (int)ini_get('upload_max_filesize') * 1000;
            $all_ext = implode(',', $this->allExtensions());
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/' . $tenantId . '/category_photos';

            $data = $request->except(['image', 'banner']);

            // Procesar imagen principal
            if($request->hasFile('image')) {
                $file = $request->image;
                $ext = $file->getClientOriginalExtension();
                $request->validate([
                    'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $rutaImagen = str_replace('storage/', 'public/', $Category->imagen);
                $this->deleteFile($rutaImagen);
                $path = $this->uploadFile($file, $rutaArchivo);
                $data['imagen'] = $path;
            }

            // Procesar banner
            if($request->hasFile('banner')) {
                $file = $request->banner;
                $ext = $file->getClientOriginalExtension();
                $request->validate([
                    'banner' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $rutaBanner = str_replace('storage/', 'public/', $Category->banner);
                $this->deleteFile($rutaBanner);
                $path = $this->uploadFile($file, $rutaArchivo);
                $data['banner'] = $path;
            }

            $data['name_en'] = $request['name_en'] === 'null' || $request['name_en'] === '' ? null : $request['name_en'];
            $data['description_en'] = $request['description_en'] === 'null' || $request['description_en'] === '' ? null : $request['description_en'];

            $Category->update($data);
        } catch (\Exception $exception) {
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
        $Category = Category::findOrFail($id);
        
        $rutaImagen = str_replace('storage/', 'public/', $Category->imagen);
        $this->deleteFile($rutaImagen);

        $rutaBanner = str_replace('storage/', 'public/', $Category->banner);
        $this->deleteFile($rutaBanner);

        $Category->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function deleteImage($id)
    {
        $this->middleware('permission:admin.destroy');
        $Category = Category::findOrFail($id);

        $rutaImagen = str_replace('storage/', 'public/', $Category->banner);
        $this->deleteFile($rutaImagen);

        $Category->update(['banner' => null]);

        return response()->json(['success' => 'Imagen eliminada'], 200);
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
        $Category = Category::findOrFail($id);

        try {
            $Category->update([
                'status' => $request['status']
            ]);
        } catch (\Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado'], 200);
    }

}
