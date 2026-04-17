<?php

namespace App\Http\Controllers\API;

use DB;
use App\User;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\API\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\Log;
use App\Traits\JWTResponseTrait;

class UserController extends Controller
{
    use FileUploadTrait;
    use JWTResponseTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->middleware('permission:admin.index|system.index');
        $users = User::with('roles')->get();

        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $this->middleware('permission:admin.create|system.create');
        try {
            $request->merge(['activation_code' => Str::random(30).time()]);
            $request->merge(['email_verified_at' => Carbon::now()]);

            if(!empty($request->password)){
                $request->merge(['password' => Hash::make($request['password'])]);
            }

            $user = User::create($request->all());

            $user->assignRole($request->role_id);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error creando el Usuario!'], 422);
        }

        return response()->json([
            "user" => $user
        ], 200);
    }

    /**
     * Sube una foto de perfil para el usuario y actualiza su perfil
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePhoto(Request $request)
    {
        $this->middleware('permission:admin.update|system.update');
        $user = auth('api')->user();
        $targetUserId = $request->input('user_id') ? intval($request->input('user_id')) : $user->id;
        if ($user->id !== $targetUserId) {
            // Aquí podrías validar permisos extra si lo necesitas
            return response()->json(['detail' => 'No tienes permiso para cambiar la foto de otro usuario.'], 403);
        }
        $dbUser = User::find($targetUserId);
        if (!$dbUser) {
            return response()->json(['detail' => 'Usuario no encontrado'], 404);
        }
        if (!$request->hasFile('file')) {
            return response()->json(['detail' => 'No se envió archivo'], 400);
        }
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $fileContentType = $file->getMimeType();
        $validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower($file->getClientOriginalExtension());
        $isValid = in_array($fileContentType, $validTypes) || in_array($ext, $validExtensions);
        if (!$isValid) {
            return response()->json(['detail' => 'Tipo de archivo no permitido. Use: jpg, jpeg, png, gif, webp'], 400);
        }
        // Guardar la foto anterior para eliminarla después
        $oldPhotoPath = $dbUser->photo;
        // Subir nueva imagen en carpeta por tenant
        $tenantId = optional(app('currentTenant'))->id ?? 'global';
        $rutaArchivo = 'public/tenants/' . $tenantId . '/user_photos';
        $path = $file->store($rutaArchivo);
        $relativePath = str_replace('public/', 'storage/', $path);
        $dbUser->photo = $relativePath;
        $dbUser->save();
        // Eliminar la foto anterior si existe
        if ($oldPhotoPath && \Storage::exists(str_replace('storage/', 'public/', $oldPhotoPath))) {
            \Storage::delete(str_replace('storage/', 'public/', $oldPhotoPath));
        }
        $url = "/uploads/{$relativePath}";
        return response()->json([
            'message' => 'Foto de perfil actualizada con éxito',
            'file_info' => [
                'file_path' => $relativePath,
                'url' => $url,
                'file_type' => $fileContentType,
                'uploaded_at' => Carbon::now()->toIso8601String()
            ],
            'user' => [
                'id' => $dbUser->id,
                'name' => $dbUser->name,
                'photo' => $dbUser->photo,
                'photo_url' => $url
            ]
        ], 200);
    }
    
    /**
     * Actualizar el perfil del usuario (versión JSON)
     * Solo permite que el usuario actual actualice su propio perfil
     */
    public function updateProfileJson(Request $request)
    {
        $this->middleware('permission:admin.update|system.update');
        $user = auth('api')->user();
        $userId = $request->input('id') ? intval($request->input('id')) : $user->id;
        if ($user->id !== $userId) {
            return response()->json(['error' => 'Solo puedes actualizar tu propio perfil'], 403);
        }
        $dbUser = User::find($userId);
        if (!$dbUser) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        $updateDict = collect($request->all())
            ->except(['id', 'photoFile', 'photo'])
            ->filter(function($v) { return !is_null($v); })
            ->toArray();
        if (empty($updateDict)) {
            return response()->json($dbUser, 200);
        }
        $dbUser->update($updateDict);
        $dbUser->refresh();
        $roles = $dbUser->roles;
        return response()->json(array_merge($dbUser->toArray(), ['roles' => $roles]), 200);
    }

    /**
     * Actualiza la contraseña del usuario autenticado
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $this->middleware('permission:admin.update|system.update');
        $user = auth('api')->user();
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
        // Verificar la contraseña actual
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Contraseña actual incorrecta'], 401);
        }
        // Actualizar la contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();
        // Solo devolver el usuario actualizado
        return response()->json([
            'user' => $user,
            'message' => 'Contraseña actualizada correctamente.'
        ], 200);
    }

    public function profile()
    {
        $this->middleware('permission:admin.index|system.index');
        return response()->json([
            "user" => auth('api')->user()
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->middleware('permission:admin.index|system.index');
        return $this.profile;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        $this->middleware('permission:admin.update|system.update');
        $user = User::findOrFail($id);

        try {
            if(!empty($request->password)){
                $request->merge(['password' => Hash::make($request['password'])]);
            }

            if(!$user->hasRole($request['role_id'])) {
                $user->syncRoles($request['role_id']);
            } else {
                $user->assignRole($request['role_id']);
            }

            $user->update($request->all());
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error actualizando el usuario'], 422);
        }

        return response()->json(['success' => 'Usuario actualizado exitosamente'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->middleware('permission:admin.destroy|system.destroy');
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json(['success' => 'Usuario eliminado'], 200);
    }

    /**
     * Activate the user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        $this->middleware('permission:admin.update|system.update');
        $user = User::findOrFail($id);
        if ($request['estado'] == 'Activar') {
            $status = 'Activado';
            $estado = 1;
        } else {
            $estado = null;
            $status = 'Inactivo';
        }

        $user->update([
            'is_active' => $estado
        ]);

        return response()->json(['success' => 'Usuario ' . $status], 200);
    }

    public function search(){
        $this->middleware('permission:admin.index|system.index');
        if ($search = \Request::get('q')) {
            $users = User::where(function($query) use ($search){
                $query->where('name','LIKE',"%$search%")
                        ->orWhere('email','LIKE',"%$search%")
                        ->orWhere('username','LIKE',"%$search%");
            })->paginate(20);
        }else{
            $users = User::latest()->paginate(5);
        }
        return $users;
    }
}
