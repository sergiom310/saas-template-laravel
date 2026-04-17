<?php

namespace App\Http\Controllers\API;

use DB;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->middleware('permission:system.index');
        $permissions = Permission::all();

        return response()->json($permissions, 200);
    }

    public function indexPermissions()
    {
        $this->middleware('permission:system.index');
        $query = "
        SELECT modulo,MAX(consultar) AS consultar,MAX(modificar) AS modificar,MAX(incluir) AS incluir,MAX(eliminar) AS eliminar
        FROM
        (
            SELECT
                t0.id,modulo,
                CASE WHEN t0.permiso = 'index' THEN t0.id ELSE '' END AS consultar,
                CASE WHEN t0.permiso = 'update' THEN t0.id ELSE '' END AS modificar,
                CASE WHEN t0.permiso = 'create' THEN t0.id ELSE '' END AS incluir,
                CASE WHEN t0.permiso = 'destroy' THEN t0.id ELSE '' END AS eliminar
            FROM
            (
                SELECT p.id,rp.permission_id,SUBSTRING(p.name, 1, POSITION('.' IN p.name) - 1) AS modulo,SUBSTRING(p.name, POSITION('.' IN p.name) + 1) AS permiso
                FROM permissions p LEFT JOIN (SELECT rp.permission_id FROM role_has_permissions rp JOIN roles r ON rp.role_id = r.id ) rp ON p.id = rp.permission_id
            ) t0
        ) t
        GROUP BY t.modulo
        ";

        $permissions = DB::select($query);

        return response()->json([
            "permissions" => $permissions
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->middleware('permission:system.create');
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
            ]);
        
            $permiso = Permission::create($validatedData);

            return response()->json(["permiso" => $permiso], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error creando el producto!'], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->middleware('permission:system.update');
        $permiso = Permission::findOrFail($id);

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
            ]);

            $permiso->update($validatedData);

            return response()->json(['success' => 'Permiso actualizado exitosamente'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error actualizando BD!'], 422);
        }
    }

    public function permissionsmodel($id)
    {
        $this->middleware('permission:system.index');
        $permisos = \DB::table('model_has_permissions')
            ->where('model_id',$id)
            ->get();

        return $permisos;
    }

    public function updatepermissionsmodel(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $permissions = $request['permisosroles'];

        $user->syncPermissions($permissions);

        return response()->json(['success' => $permissions], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->middleware('permission:system.destroy');
        $permiso = Permission::findOrFail($id);

        $permiso->delete();

       return response()->json(['success' => 'permiso eliminado'], 200);
    }

    public function search(){
        $this->middleware('permission:system.index');
        if ($search = \Request::get('q')) {
            $permisos = Permission::where(function($query) use ($search){
                $query->where('name','LIKE',"%$search%")
                        ->orWhere('guard_name','LIKE',"%$search%");
            })->paginate(20);

            return $permisos;
        } else {
            $permisos = Permission::latest()->paginate(10);
        }
    }
}
