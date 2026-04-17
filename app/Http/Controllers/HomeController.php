<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use App\User;
use DB;

/**
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Zapatos"),
 *     @OA\Property(property="name_en", type="string", example="Shoes"),
 *     @OA\Property(property="slug", type="string", example="zapatos"),
 *     @OA\Property(property="icono", type="string", example="fa-shoe-prints"),
 *     @OA\Property(property="imagen", type="string", example="category.jpg"),
 *     @OA\Property(property="subCategories", type="array", @OA\Items(ref="#/components/schemas/Category"))
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="Zapato de cuero"),
 *     @OA\Property(property="status", type="string", example="Activo"),
 *     @OA\Property(property="price", type="number", format="float", example=150.50),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category"))
 * )
 *
 * @OA\Schema(
 *     schema="ImageProduct",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="image", type="string", example="product.jpg"),
 *     @OA\Property(property="description", type="string", example="Vista lateral")
 * )
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * @OA\Get(
     *     path="/categoriesHome",
     *     summary="Obtener categorías con sus subcategorías",
     *     tags={"Home"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de categorías",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category"))
     *     )
     * )
     */
    public function categoriesHome()
    {
        $Category = Category::select('id','name','name_en', 'slug','icono','imagen')->where('status', 'Activo')->whereNull('parent_id')->get();
        if (null != $Category) {
            foreach($Category as $padre) {
                $hijos = Category::select('id','name','name_en','slug','icono','imagen')->where('parent_id', $padre->id)->get();
                if (null != $hijos) {
                    $padre->subCategories = $hijos;
                }
            }
        }

        return response()->json($Category, 200);
    }

    /**
     * @OA\Get(
     *     path="/products/{category_slug}",
     *     summary="Listar productos por categoría",
     *     tags={"Home"},
     *     @OA\Parameter(
     *         name="category_slug",
     *         in="path",
     *         description="Slug de la categoría",
     *         required=true,
     *         @OA\Schema(type="string", example="zapatos")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Cantidad de productos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos de la categoría",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="category", ref="#/components/schemas/Category")
     *         )
     *     )
     * )
     */
    public function listProducts($category_id)
    {
        $Category = Category::where('slug', $category_id)->firstOrFail();

        $limit = \Request::get('limit', 20);
        $query = $Category->products()->where('status', 'Activo');
        $Product = \Request::has('page') ? $query->paginate($limit) : $query->get();

        return response()->json([
            "data" => $Product,
            "category" => $Category
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/product/{id}",
     *     summary="Obtener detalles de un producto",
     *     tags={"Home"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del producto",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del producto",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", ref="#/components/schemas/Product"),
     *             @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/ImageProduct"))
     *         )
     *     )
     * )
     */
    public function productDetail($id)
    {
        $Product = Product::with('categories')->where('id', $id)->first();
        
        $imagesProduct = \DB::table('image_product')
        ->select('id','image','description')
        ->where('product_id','=',$id)
        ->get();

        return response()->json([
            "data" => $Product,
            "images" => $imagesProduct
        ], 200);
    }

    public function index()
    {
        return response()->json([
            "data" => 'Index'
        ], 200);
    }

    public function reset($remember_token)
    {
        $user = User::where('remember_token', $remember_token)->first();

        if (null !== $user) {
            $mensaje = 'Ok';
        } else {
            $mensaje = 'Usuario no encontrado en el sistema!';
        }
        
        return view('auth.resetearEmail', compact('mensaje'));
    }

    public function activateAccount($activation_code)
    {
        $user = User::where('activation_code', $activation_code)->first();

        if (null !== $user) {
            if ($user->email_verified_at !== null) {
                $mensaje = 'Email ya fue verificado!';
            } else {            
                $user->update([
                    'email_verified_at' => Carbon::now()
                ]);
                $mensaje = 'Email verificado!';
            }
        } else {
            $mensaje = 'Email no encontrado en el sistema!';
        }
        
        return view('auth.verificarEmail', compact('mensaje'));
    }

}
