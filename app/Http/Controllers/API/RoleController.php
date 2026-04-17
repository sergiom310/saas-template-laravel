<?php

namespace App\Http\Controllers\API;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Tag(
 *     name="Roles",
 *     description="Endpoints para gestión de roles y permisos"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin"),
 *     @OA\Property(property="guard_name", type="string", example="api"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-12T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-12T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin.index"),
 *     @OA\Property(property="guard_name", type="string", example="api"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-12T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-12T12:00:00Z")
 * )
 */
class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/roles",
     *     tags={"Roles"},
     *     summary="Listar todos los roles",
     *     description="Obtiene todos los roles registrados",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de roles",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Role"))
     *     ),
     *     @OA\Response(response=401, description="No autorizado")
     * )
     */
    public function index()
    {
        $this->middleware('permission:admin.index');
        $roles = Role::all();
        return response()->json($roles, 200);
    }

    /**
     * @OA\Post(
     *     path="/roles",
     *     tags={"Roles"},
     *     summary="Crear un nuevo rol",
     *     description="Crea un rol y asigna permisos opcionales",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Admin"),
     *             @OA\Property(property="permisosroles", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=422, description="Error en validación o creación")
     * )
     */
    public function store(Request $request)
    {
        $this->middleware('permission:admin.create');
        try {
            $request->validate(['name' => 'required|string|max:191']);

            $role = Role::create([
                'name' => $request['name'],
                'guard_name' => 'api'
            ]);

            if ($permisos = $request['permisosroles']) {
                $permissionNames = Permission::whereIn('id', $permisos)->pluck('name')->toArray();
                $role->givePermissionTo($permissionNames);
            }

            return response()->json(["role" => $role], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error creando el Role!'], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/roles/{id}/permissions",
     *     tags={"Roles"},
     *     summary="Listar permisos de un rol",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Permisos del rol",
     *         @OA\JsonContent(
     *             @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission"))
     *         )
     *     )
     * )
     */
    public function permissions($id)
    {
        $this->middleware('permission:admin.index');
        $permisos = \DB::table('permissions')
            ->leftJoin('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_id', $id)
            ->get();

        return response()->json([
            "permissions" => $permisos
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/roles/{id}",
     *     tags={"Roles"},
     *     summary="Actualizar un rol",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","guard_name"},
     *             @OA\Property(property="name", type="string", example="Editor"),
     *             @OA\Property(property="guard_name", type="string", example="api"),
     *             @OA\Property(property="permisosroles", type="array", @OA\Items(type="integer", example=2))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rol actualizado exitosamente"),
     *     @OA\Response(response=422, description="Error en actualización")
     * )
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:admin.update');
        $role = Role::findOrFail($id);

        try {
            $request->validate([
                'name' => 'required|string|max:191',
                'guard_name' => 'required|string|max:191'
            ]);

            $role->update([
                'name' => $request['name'],
                'guard_name' => $request['guard_name']
            ]);

            $permissions = $request['permisosroles'];
            $permissionNames = Permission::whereIn('id', $permissions)->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);

            return response()->json(['success' => 'Role actualizado exitosamente'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error actualizando BD!'], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/roles/{id}",
     *     tags={"Roles"},
     *     summary="Eliminar un rol",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rol eliminado"),
     *     @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy');
        $permiso = Role::findOrFail($id);

        $permiso->delete();

        return response()->json(['success' => 'Role eliminado'], 200);
    }

    /**
     * @OA\Get(
     *     path="/roles/search",
     *     tags={"Roles"},
     *     summary="Buscar roles por nombre o guard_name",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Resultados de búsqueda",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Role"))
     *     )
     * )
     */
    public function search()
    {
        $this->middleware('permission:admin.index');
        if ($search = \Request::get('q')) {
            $permisos = Role::where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('guard_name', 'LIKE', "%$search%");
            })->paginate(20);

            return $permisos;
        } else {
            $permisos = Permission::latest()->paginate(10);
        }
    }
}
