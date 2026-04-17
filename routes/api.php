<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductsController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\TipoVehiculoController;
use App\Http\Controllers\API\TarifaController;
use App\Http\Controllers\API\TarifaReglaController;
use App\Http\Controllers\API\FacturaController;
use App\Http\Controllers\API\MetodoPagoController;
use App\Http\Controllers\MetodosPagoAgdController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\PagoController;
use App\Http\Controllers\API\CuadreCajaController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\VentaController;

// Open routes
Route::get('/', [HomeController::class, 'index']);

// Ruta de prueba para verificar tenant status
Route::get('/test-tenant-status', function () {
    $tenant = \App\Models\Tenant\CustomTenantModel::current();
    return response()->json([
        'tenant_detected' => $tenant ? true : false,
        'tenant_id' => $tenant?->id,
        'tenant_domain' => $tenant?->domain,
        'tenant_name' => $tenant?->name,
        'is_active' => $tenant?->is_active,
        'middleware_executed' => true
    ]);
});

//Route::get('menus', [MenuController::class, 'index']);

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
Route::group(['middleware' => [\App\Http\Middleware\JwtCookieMiddleware::class, \App\Http\Middleware\JwtAuthMiddleware::class]], function () {

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
    Route::get('modulos-admin', [\App\Http\Controllers\ModuloController::class, 'index']);
    Route::post('modulos-admin', [\App\Http\Controllers\ModuloController::class, 'store']);
    Route::get('modulos-admin/{id}', [\App\Http\Controllers\ModuloController::class, 'show']);
    Route::put('modulos-admin/{id}', [\App\Http\Controllers\ModuloController::class, 'update']);
    Route::delete('modulos-admin/{id}', [\App\Http\Controllers\ModuloController::class, 'destroy']);
    Route::put('modulos-admin/{id}/toggle-status', [\App\Http\Controllers\ModuloController::class, 'toggleStatus']);
    
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

    // Métodos de Pago para sistema de agendas
    Route::get('agd-metodos-pago', [MetodosPagoAgdController::class, 'index']);
    Route::get('agd-metodos-pago/activos', [MetodosPagoAgdController::class, 'activos']);
    Route::post('agd-metodos-pago', [MetodosPagoAgdController::class, 'store']);
    Route::get('agd-metodos-pago/{id}', [MetodosPagoAgdController::class, 'show']);
    Route::put('agd-metodos-pago/{id}', [MetodosPagoAgdController::class, 'update']);
    Route::delete('agd-metodos-pago/{id}', [MetodosPagoAgdController::class, 'destroy']);
    Route::put('agd-metodos-pago/{id}/toggle-status', [MetodosPagoAgdController::class, 'toggleStatus']);
    
    // Especialidades
    Route::get('especialidades', [\App\Http\Controllers\EspecialidadController::class, 'index']);
    Route::post('especialidades', [\App\Http\Controllers\EspecialidadController::class, 'store']);
    Route::get('especialidades/{id}', [\App\Http\Controllers\EspecialidadController::class, 'show']);
    Route::put('especialidades/{id}', [\App\Http\Controllers\EspecialidadController::class, 'update']);
    Route::delete('especialidades/{id}', [\App\Http\Controllers\EspecialidadController::class, 'destroy']);
    Route::put('especialidades/status/{id}', [\App\Http\Controllers\EspecialidadController::class, 'activate']);

    // Clientes Agenda
    Route::get('clientes-agenda', [\App\Http\Controllers\ClienteController::class, 'index']);
    Route::post('clientes-agenda', [\App\Http\Controllers\ClienteController::class, 'store']);
    Route::get('clientes-agenda/{id}', [\App\Http\Controllers\ClienteController::class, 'show']);
    Route::put('clientes-agenda/{id}', [\App\Http\Controllers\ClienteController::class, 'update']);
    Route::delete('clientes-agenda/{id}', [\App\Http\Controllers\ClienteController::class, 'destroy']);

    // Franjas Horarias
    Route::get('franjas-horarias', [\App\Http\Controllers\FranjaHorariaController::class, 'index']);
    Route::post('franjas-horarias', [\App\Http\Controllers\FranjaHorariaController::class, 'store']);
    Route::get('franjas-horarias/{id}', [\App\Http\Controllers\FranjaHorariaController::class, 'show']);
    Route::put('franjas-horarias/{id}', [\App\Http\Controllers\FranjaHorariaController::class, 'update']);
    Route::delete('franjas-horarias/{id}', [\App\Http\Controllers\FranjaHorariaController::class, 'destroy']);

    // Profesionales
    Route::get('profesionales', [\App\Http\Controllers\ProfesionalController::class, 'index']);
    Route::post('profesionales', [\App\Http\Controllers\ProfesionalController::class, 'store']);
    Route::get('profesionales/{id}', [\App\Http\Controllers\ProfesionalController::class, 'show']);
    Route::put('profesionales/{id}', [\App\Http\Controllers\ProfesionalController::class, 'update']);
    Route::delete('profesionales/{id}', [\App\Http\Controllers\ProfesionalController::class, 'destroy']);

    // Agenda
    Route::get('agenda', [\App\Http\Controllers\AgendaController::class, 'index']);
    Route::get('agenda/disponibilidad', [\App\Http\Controllers\AgendaController::class, 'disponibilidad']);
    Route::post('agenda', [\App\Http\Controllers\AgendaController::class, 'store']);
    Route::get('agenda/{id}', [\App\Http\Controllers\AgendaController::class, 'show']);
    Route::put('agenda/{id}', [\App\Http\Controllers\AgendaController::class, 'update']);
    Route::delete('agenda/{id}', [\App\Http\Controllers\AgendaController::class, 'destroy']);
    Route::put('agenda/{id}/estado', [\App\Http\Controllers\AgendaController::class, 'cambiarEstado']);

    // Pagos de Agenda
    // Route::get('metodos-pago', [\App\Http\Controllers\PagoAgendaController::class, 'metodosPago']);
    Route::get('pagos/reporte/pdf', [\App\Http\Controllers\PagoAgendaController::class, 'reportePDF']);
    Route::get('pagos/reporte/excel', [\App\Http\Controllers\PagoAgendaController::class, 'reporteExcel']);
    Route::get('pagos', [\App\Http\Controllers\PagoAgendaController::class, 'index']);
    Route::post('pago-agenda', [\App\Http\Controllers\PagoAgendaController::class, 'store']);
    Route::put('pagos/{id}', [\App\Http\Controllers\PagoAgendaController::class, 'update']);
    Route::delete('pagos/{id}', [\App\Http\Controllers\PagoAgendaController::class, 'destroy']);

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
    Route::post('tagcreate', [TagController::class, 'store']);
    Route::post('tagupdate', [TagController::class, 'update']);

    // Products
    Route::get('products', [ProductsController::class, 'index']);
    Route::post('products', [ProductsController::class, 'store']);
    Route::get('products/{id}', [ProductsController::class, 'show']);
    Route::put('products/{id}', [ProductsController::class, 'update']);
    Route::delete('products/{id}', [ProductsController::class, 'destroy']);
    Route::put('products/{id}/status', [ProductsController::class, 'activate']);
    Route::post('products/{id}/images', [ProductsController::class, 'storeImage']);
    Route::delete('products/images/{id}', [ProductsController::class, 'deleteImage']);

    // Ventas de Productos
    Route::get('ventas', [VentaController::class, 'index']);
    Route::post('ventas', [VentaController::class, 'store']);
    Route::get('ventas/estadisticas', [VentaController::class, 'estadisticas']);
    Route::get('ventas/{id}', [VentaController::class, 'show']);
    Route::put('ventas/{id}/estado', [VentaController::class, 'updateEstado']);
    Route::post('ventas/{id}/pagos', [VentaController::class, 'addPago']);
    Route::delete('ventas/{id}', [VentaController::class, 'destroy']);

    // Tipo Vehiculo
    Route::get('tipo-vehiculos', [TipoVehiculoController::class, 'index']);
    Route::post('tipo-vehiculos', [TipoVehiculoController::class, 'store']);
    Route::get('tipo-vehiculos/{id}', [TipoVehiculoController::class, 'show']);
    Route::put('tipo-vehiculos/{id}', [TipoVehiculoController::class, 'update']);
    Route::delete('tipo-vehiculos/{id}', [TipoVehiculoController::class, 'destroy']);
    Route::put('tipo-vehiculos/tipovehiculostatus/{id}', [TipoVehiculoController::class, 'activate']);
    Route::get('tipo-vehiculos-activos', [TipoVehiculoController::class, 'registrosActivos']);

    // Tarifas
    Route::get('tarifas', [TarifaController::class, 'index']);
    Route::post('tarifas', [TarifaController::class, 'store']);
    Route::get('tarifas/{id}', [TarifaController::class, 'show']);
    Route::put('tarifas/{id}', [TarifaController::class, 'update']);
    Route::delete('tarifas/{id}', [TarifaController::class, 'destroy']);
    Route::put('tarifas/tarifastatus/{id}', [TarifaController::class, 'activate']);

    // Tarifa Reglas
    Route::get('tarifa-reglas', [TarifaReglaController::class, 'index']);
    Route::post('tarifa-reglas', [TarifaReglaController::class, 'store']);
    Route::get('tarifa-reglas/{id}', [TarifaReglaController::class, 'show']);
    Route::put('tarifa-reglas/{id}', [TarifaReglaController::class, 'update']);
    Route::delete('tarifa-reglas/{id}', [TarifaReglaController::class, 'destroy']);

    // Facturas
    Route::get('facturas', [FacturaController::class, 'index']);
    Route::post('facturas', [FacturaController::class, 'store']);
    Route::post('facturas/calcular-tarifa', [FacturaController::class, 'calcularTarifa']);
    Route::get('facturas/{id}', [FacturaController::class, 'show']);
    Route::put('facturas/{id}', [FacturaController::class, 'update']);
    Route::delete('facturas/{id}', [FacturaController::class, 'destroy']);
    Route::post('facturas/{id}/confirmar-cobro', [FacturaController::class, 'confirmarCobro']);
    // Nuevo endpoint para calcular tarifa sin cerrar factura

    // Métodos de Pago para sistema de parqueaderos
    Route::get('metodos-pago', [MetodoPagoController::class, 'index']);
    Route::get('metodos-pago/activos', [MetodoPagoController::class, 'activos']);
    Route::post('metodos-pago', [MetodoPagoController::class, 'store']);
    Route::get('metodos-pago/{id}', [MetodoPagoController::class, 'show']);
    Route::put('metodos-pago/{id}', [MetodoPagoController::class, 'update']);
    Route::delete('metodos-pago/{id}', [MetodoPagoController::class, 'destroy']);
    Route::put('metodos-pago/{id}/toggle-status', [MetodoPagoController::class, 'toggleStatus']);

    // Clientes
    Route::get('clientes', [ClienteController::class, 'index']);
    Route::post('clientes', [ClienteController::class, 'store']);
    Route::get('clientes/{id}', [ClienteController::class, 'show']);
    Route::put('clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('clientes/{id}', [ClienteController::class, 'destroy']);
    Route::put('clientes/{id}/status', [ClienteController::class, 'activate']);

    // Pagos (Comentado - usar PagoAgendaController para sistema de agendas)
    // Route::get('pagos', [PagoController::class, 'index']);
    // Route::post('pagos', [PagoController::class, 'store']);
    // Route::get('pagos/{id}', [PagoController::class, 'show']);
    // Route::put('pagos/{id}', [PagoController::class, 'update']);
    // Route::delete('pagos/{id}', [PagoController::class, 'destroy']);
    // Route::get('pagos-cliente/{codCli}', [PagoController::class, 'pagosPorCliente']);

    // Cuadre de caja
    Route::get('cuadres', [CuadreCajaController::class, 'index']);
    Route::post('cuadres', [CuadreCajaController::class, 'store']);
    Route::post('cuadres/{id}/close', [CuadreCajaController::class, 'close']);
    Route::get('cuadres/{id}/resumen', [CuadreCajaController::class, 'resumen']);
    Route::get('cuadres/{id}/detalle', [CuadreCajaController::class, 'detalle']);

    // Ticket de entrada
    Route::get('ticket/entrada/{facturaId}', [\App\Http\Controllers\API\TicketController::class, 'entrada']);

    // Empresa
    Route::get('empresa', [EmpresaController::class, 'show']);
    Route::post('empresa', [EmpresaController::class, 'store']);
    Route::post('empresa/logo', [EmpresaController::class, 'uploadLogo']);

    // Configuración General
    Route::get('configuracion', [\App\Http\Controllers\ConfiguracionController::class, 'index']);
    Route::post('configuracion/update', [\App\Http\Controllers\ConfiguracionController::class, 'update']);

    // Gastos
    Route::get('gastos', [\App\Http\Controllers\GastoController::class, 'index']);
    Route::post('gastos', [\App\Http\Controllers\GastoController::class, 'store']);
    Route::put('gastos/{id}', [\App\Http\Controllers\GastoController::class, 'update']);
    Route::delete('gastos/{id}', [\App\Http\Controllers\GastoController::class, 'destroy']);

    // Reportes
    Route::get('reportes/datos', [\App\Http\Controllers\ReporteController::class, 'obtenerDatos']);
    Route::get('reportes/pdf-detallado', [\App\Http\Controllers\ReporteController::class, 'pdfDetallado']);
    Route::get('reportes/pdf-resumido', [\App\Http\Controllers\ReporteController::class, 'pdfResumido']);
    Route::get('reportes/excel-detallado', [\App\Http\Controllers\ReporteController::class, 'excelDetallado']);
    Route::get('reportes/excel-resumido', [\App\Http\Controllers\ReporteController::class, 'excelResumido']);
});

Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found.'], 404);
});
