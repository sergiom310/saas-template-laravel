<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ImageProduct;
use Illuminate\Http\Request;
use App\Http\Requests\API\ProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Bitacora;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\FileUploadTrait;

class ProductsController extends Controller
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

        if ($page = \Request::get('page')) {
            $limit = \Request::get('limit', 20);
            $Product = Product::with(['brand', 'categories', 'images'])->paginate($limit);
        } else {
            $Product = Product::with(['brand', 'categories', 'images'])->get();

            foreach($Product as $pro) {
                $categorys = $pro->categories->toArray();
                $pro->category = count($categorys) > 0
                ? implode(", ", array_map(fn($a) => $a['name'], $categorys))
                : '';

                $images = $pro->images->toArray();
            }
        }

        return response()->json($Product, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        $this->middleware('permission:admin.create');

        try {
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/' . $tenantId . '/product_photos';

            if($request->hasFile('image')) {
                $file = $request->image;
                $max_size = (int)ini_get('upload_max_filesize') * 1000;
                $all_ext = implode(',', $this->allExtensions());
                $request->validate([
                    'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $ext = $file->getClientOriginalExtension();


                $path = $this->uploadFile($file, $rutaArchivo);

                $request['cover_img'] = $path;
            }

            $user = auth()->user();

            $request['show_price'] = (bool) $request->show_price;
            $request['is_featured'] = (bool) $request->is_featured;
            $request['stock_visible'] = (bool) $request->stock_visible;
            $request['allow_backorder'] = (bool) $request->allow_backorder;
            $request['discount_active'] = (bool) $request->discount_active;
            $request['show_related'] = (bool) $request->show_related;
            $request['min_order_qty'] = (int) $request->show_related;
            $request['description'] = $request['description'] === 'null' || $request['description'] === '' ? null : $request['description'];
            $request['description_en'] = $request['description_en'] === 'null' || $request['description_en'] === '' ? null : $request['description_en'];
                        
            $Product = Product::create($request->all());
            $vCategories = json_decode($request->input('category_id'), true) ?? [];
            $vCategories = array_map('intval', $vCategories);
            $Product->categories()->sync($vCategories);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json($Product, 200);
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
        $response = Product::whereId($id)->get();

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request)
    {
        $this->middleware('permission:admin.update');
        $Product = Product::findOrFail($request->id);
       
        try {
            $tenantId = optional(app('currentTenant'))->id ?? 'global';
            $rutaArchivo = 'public/tenants/' . $tenantId . '/product_photos';
            // anexar imagen del producto
            if($request->hasFile('image') and $Product->cover_img != $request->image) {
                $file = $request->image;
                $max_size = (int)ini_get('upload_max_filesize') * 1000;
                $all_ext = implode(',', $this->allExtensions());
                $request->validate([
                    'image' => 'file|mimes:' . $all_ext . '|max:' . $max_size
                ]);
                $ext = $file->getClientOriginalExtension();


                $rutaCoverImg = str_replace('storage/', 'public/', $Product->cover_img);
                $this->deleteFile($rutaCoverImg);    

                $path = $this->uploadFile($file, $rutaArchivo);
                $Product->cover_img = $path;
            }

            $request['show_price'] = (bool) $request->show_price;
            $request['is_featured'] = (bool) $request->is_featured;
            $request['stock_visible'] = (bool) $request->stock_visible;
            $request['allow_backorder'] = (bool) $request->allow_backorder;
            $request['discount_active'] = (bool) $request->discount_active;
            $request['show_related'] = (bool) $request->show_related;
            $request['min_order_qty'] = (int) $request->show_related;
            $request['description'] = $request['description'] === 'null' || $request['description'] === '' ? null : $request['description'];
            $request['description_en'] = $request['description_en'] === 'null' || $request['description_en'] === '' ? null : $request['description_en'];

            // actualizo toda la info del producto
            $Product->update($request->all());
            $vCategories = json_decode($request->input('category_id'), true) ?? [];
            $vCategories = array_map('intval', $vCategories);
            $Product->categories()->sync($vCategories);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => 'Registro actualizado exitosamente'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function storeImage(Request $request)
    {
        $this->middleware('permission:admin.update');
        $detalle1 = $request->detail_img1 != 'undefined' ? $request->detail_img1 : null;
        $file = $request->file;
        $max_size = (int)ini_get('upload_max_filesize') * 1000;
        $all_ext = implode(',', $this->allExtensions());
        $request->validate([
            'file' => 'file|mimes:' . $all_ext . '|max:' . $max_size
        ]);
        $ext = $file->getClientOriginalExtension();

        $tenantId = optional(app('currentTenant'))->id ?? 'global';
        $rutaArchivo = 'public/tenants/' . $tenantId . '/product_photos';
        $path = $this->uploadFile($file, $rutaArchivo);

        $image = ImageProduct::create([
            'product_id' => $request->id,
            'description' => $detalle1,
            'image' => $path
        ]);

        $imagenes = ImageProduct::where('product_id', $request->id)->get();

        return response()->json($imagenes, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clientes  $clientes
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        $this->middleware('permission:admin.update');
        $Product = Product::findOrFail($id);

        try {
            $Product->update([
                'status' => $request['status']
            ]);
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
    public function deleteImage($id)
    {
        $this->middleware('permission:admin.destroy');
        $ImageProduct = ImageProduct::findOrFail($id);

        $rutaImagen = str_replace('storage/', 'public/', $ImageProduct->image);
        $this->deleteFile($rutaImagen);

        $ImageProduct->delete();

        return response()->json(['success' => 'Imagen eliminada'], 200);
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
        $Product = Product::findOrFail($id);

        $tenantId = optional(app('currentTenant'))->id ?? 'global';
        $rutaArchivo = 'public/tenants/' . $tenantId . '/product_photos';

        // eliminamos la imagen principal del producto en la tabla products
        $rutaCoverImg = str_replace('storage/', 'public/', $Product->cover_img);
        $this->deleteFile($rutaCoverImg);

        // borrar las imagenes del producto de base y repo
        $ImageProduct = ImageProduct::where('product_id', $Product->id)->get();
        if (null != $ImageProduct) {
            foreach($ImageProduct as $imagen) {                
                $rutaImagenProducto = str_replace('storage/', 'public/', $imagen->image);
                $this->deleteFile($rutaImagenProducto);        
            }
            ImageProduct::where('product_id', $Product->id)->delete();
        }
        // limpiar tabla categorias_producto para el producto eliminado
        $Product->categories()->detach();

        // borrar producto
        $Product->delete();

        return response()->json(['success' => 'Registro eliminado'], 200);
    }

    public function search(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('description', 'LIKE', "%{$request->search}%")
                ->orWhereHas('tags', function ($q) use ($request) {
                    $q->where('name', 'LIKE', "%{$request->search}%");
                });
            });
        }

        return response()->json($query->get());
    }
}
