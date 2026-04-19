<?php

use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\EmpresaController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PedidoController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\ProductsController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetodosPagoController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\TenantController;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\JwtCookieMiddleware;
use App\Models\Tenant\CustomTenantModel;
use Illuminate\Support\Facades\Route;

// Open routes
Route::get('/', [HomeController::class, 'index']);

// Ruta de prueba para verificar tenant status
Route::get('/test-tenant-status', function () {
    $tenant = CustomTenantModel::current();

    return response()->json([
        'tenant_detected' => $tenant ? true : false,
        'tenant_id' => $tenant?->id,
        'tenant_domain' => $tenant?->domain,
        'tenant_name' => $tenant?->name,
        'is_active' => $tenant?->is_active,
        'middleware_executed' => true,
    ]);
});

Route::get('categoriesHome', [HomeController::class, 'categoriesHome']);
Route::get('productslist/{category_id}', [HomeController::class, 'listProducts']);
Route::get('productdetail/{id}', [HomeController::class, 'productDetail']);

// Módulos
Route::get('modulos', [TenantController::class, 'listarModulos']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register-tenant', [AuthController::class, 'registerTenant']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('reset-password', [AuthController::class, 'resetPasswordRequest']);
    Route::post('confirm-reset-password', [AuthController::class, 'confirmResetPassword']);
    Route::get('validatereset/{remember_token}', [AuthController::class, 'validatereset']);
    Route::get('verify-email', [AuthController::class, 'verifyEmail']);
    Route::get('check-verification-token', [AuthController::class, 'checkVerificationToken']);
    Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
});

// Protected routes
Route::group(['middleware' => [JwtCookieMiddleware::class, JwtAuthMiddleware::class]], function () {

    // Tenants
    Route::get('tenants', [TenantController::class, 'index']);
    Route::post('tenants', [TenantController::class, 'store']);
    Route::get('tenants/{id}', [TenantController::class, 'show']);
    Route::put('tenants/{id}', [TenantController::class, 'update']);
    Route::delete('tenants/{id}', [TenantController::class, 'destroy']);
    Route::post('tenants/{id}/migrate', [TenantController::class, 'migrate']);
    Route::post('tenants/{id}/create-database', [TenantController::class, 'createDatabase']);
    Route::post('tenants/{id}/pago-modulo', [TenantController::class, 'pagoModuloTenant']);
    Route::get('tenants-pagos', [TenantController::class, 'listarPagos']);
    Route::get('mis-pagos', [TenantController::class, 'listarMisPagos']);

    // Módulos
    Route::get('modulos-admin', [ModuloController::class, 'index']);
    Route::post('modulos-admin', [ModuloController::class, 'store']);
    Route::get('modulos-admin/{id}', [ModuloController::class, 'show']);
    Route::put('modulos-admin/{id}', [ModuloController::class, 'update']);
    Route::delete('modulos-admin/{id}', [ModuloController::class, 'destroy']);
    Route::put('modulos-admin/{id}/toggle-status', [ModuloController::class, 'toggleStatus']);

    // Permissions
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::put('permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('permissions/{id}', [PermissionController::class, 'destroy']);
    Route::get('permission2', [PermissionController::class, 'indexPermissions']);
    Route::get('permissionsrole/{id}', [RoleController::class, 'permissions']);
    Route::get('permissionsmodel/{id}', [PermissionController::class, 'permissionsmodel']);
    Route::put('permissionsmodel/{id}', [PermissionController::class, 'updatepermissionsmodel']);

    // Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);

    // Users
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::put('users/status/{id}', [UserController::class, 'activate']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::post('updateprofilejson', [UserController::class, 'updateProfileJson']);
    Route::post('upload-profile-photo', [UserController::class, 'uploadProfilePhoto']);
    Route::post('update-password', [UserController::class, 'updatePassword']);
    Route::get('findUser', [UserController::class, 'search']);

    // Brands
    Route::get('brands', [BrandController::class, 'index']);
    Route::post('brands', [BrandController::class, 'store']);
    Route::get('brands/{id}', [BrandController::class, 'show']);
    Route::put('brands/{id}', [BrandController::class, 'update']);
    Route::delete('brands/{id}', [BrandController::class, 'destroy']);
    Route::put('brands/brandstatus/{id}', [BrandController::class, 'activate']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    Route::put('categories/categorystatus/{id}', [CategoryController::class, 'activate']);
    Route::delete('categories/deletecategoryimage/{id}', [CategoryController::class, 'deleteImage']);

    // Tags
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::get('tags/{id}', [TagController::class, 'show']);
    Route::put('tags/{id}', [TagController::class, 'update']);
    Route::delete('tags/{id}', [TagController::class, 'destroy']);

    // Products
    Route::get('products', [ProductsController::class, 'index']);
    Route::post('products', [ProductsController::class, 'store']);
    Route::get('products/{id}', [ProductsController::class, 'show']);
    Route::put('products/{id}', [ProductsController::class, 'update']);
    Route::delete('products/{id}', [ProductsController::class, 'destroy']);
    Route::put('products/{id}/status', [ProductsController::class, 'activate']);
    Route::post('products/{id}/images', [ProductsController::class, 'storeImage']);
    Route::delete('products/images/{id}', [ProductsController::class, 'deleteImage']);

    // Métodos de Pago
    Route::get('metodos-pago', [MetodosPagoController::class, 'index']);
    Route::get('metodos-pago/activos', [MetodosPagoController::class, 'activos']);
    Route::post('metodos-pago', [MetodosPagoController::class, 'store']);
    Route::get('metodos-pago/{id}', [MetodosPagoController::class, 'show']);
    Route::put('metodos-pago/{id}', [MetodosPagoController::class, 'update']);
    Route::delete('metodos-pago/{id}', [MetodosPagoController::class, 'destroy']);
    Route::put('metodos-pago/{id}/toggle-status', [MetodosPagoController::class, 'toggleStatus']);

    // Empresa
    Route::get('empresa', [EmpresaController::class, 'index']);
    Route::post('empresa', [EmpresaController::class, 'store']);
    Route::put('empresa/{id}', [EmpresaController::class, 'update']);

    // Ventas / Órdenes de Productos
    Route::get('ventas', [OrderController::class, 'index']);
    Route::post('ventas', [OrderController::class, 'store']);
    Route::get('ventas/estadisticas', [OrderController::class, 'estadisticas']);
    Route::get('ventas/{id}', [OrderController::class, 'show']);
    Route::put('ventas/{id}/estado', [OrderController::class, 'updateEstado']);
    Route::post('ventas/{id}/pagos', [OrderController::class, 'addPago']);
    Route::delete('ventas/{id}', [OrderController::class, 'destroy']);

    // Pedidos de clientes (carrito → orden → admin procesa pago)
    Route::get('pedidos', [PedidoController::class, 'index']);
    Route::post('pedidos', [PedidoController::class, 'store']);
    Route::get('pedidos/estadisticas', [PedidoController::class, 'estadisticas']);
    Route::get('pedidos/mis-pedidos', [PedidoController::class, 'misOrdenes']);
    Route::get('pedidos/{id}', [PedidoController::class, 'show']);
    Route::put('pedidos/{id}/status', [PedidoController::class, 'updateStatus']);
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Page Not Found.'], 404);
});
