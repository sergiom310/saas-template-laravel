<?php
namespace App\Http\Controllers;

use DB;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserStoreRequest;
use App\Services\CustomPasswordBrokerManager;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant\CustomTenantModel;
use App\Http\Requests;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\JWTResponseTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyAccountMail;
use App\Mail\RegistrationSuccessMail;
use App\Mail\BienvenidaMail;
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
    use JWTResponseTrait;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register','registerTenant','reset','resetPasswordRequest','confirmResetPassword','verifyEmail', 'checkVerificationToken', 'resendVerificationEmail','logout']]);
    }

    /**
     * Create users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerTenant(UserStoreRequest $request)
    {
        $recaptchaSiteKey = request('recaptcha_token');
        if (trim($recaptchaSiteKey)) {
            $recaptchaSecret = "6Ld8-IgrAAAAAB1HouiMlq7bDv_r6Ok6XvSauqBO";
            $apiCall = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaSiteKey";
            $response = file_get_contents($apiCall);
            $responseKeys = json_decode($response, true);
            if(intval($responseKeys["success"]) !== 1) {
                return response()->json(['error' => 'Recaptcha no valido'], 401);
            }
        }

        /** @var User $user */
        $validatedData = $request->validated();

        // 1. Validar datos ANTES del try-catch para que Laravel maneje ValidationException correctamente
        $validated = $request->validate([
            'name' => 'required|string',
            'domain' => 'required|string|unique:landlord.tenants,domain',
            'name_company' => 'required|string|unique:landlord.tenants,name_company',
            'email' => 'required|email|unique:landlord.users,email|unique:landlord.tenants,owner_email',
            'password' => 'required|string|min:6',
            'modulos' => 'required|array|min:1',
            'modulos.*.modulo_id' => 'required|integer|exists:landlord.modulos,id',
            'modulos.*.metodo_pago' => 'required|string|in:mensual,anual',
        ], [
            // Mensajes personalizados
            'name.required' => 'El nombre es obligatorio',
            'domain.required' => 'El dominio es obligatorio',
            'domain.unique' => 'Este dominio ya está registrado. Por favor elige otro dominio.',
            'name_company.required' => 'El nombre de la empresa es obligatorio',
            'name_company.unique' => 'Esta empresa ya está registrada. Por favor usa otro nombre de empresa.',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser una dirección válida',
            'email.unique' => 'Este email ya está registrado como propietario de otro tenant o usuario. Por favor usa otro email.',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'modulos.required' => 'Debes seleccionar al menos un módulo',
            'modulos.min' => 'Debes seleccionar al menos un módulo',
            'modulos.*.modulo_id.required' => 'El ID del módulo es obligatorio',
            'modulos.*.modulo_id.exists' => 'El módulo seleccionado no existe',
            'modulos.*.metodo_pago.required' => 'El método de pago es obligatorio',
            'modulos.*.metodo_pago.in' => 'El método de pago debe ser mensual o anual',
        ]);

        try {
            // 2. Crear tenant en landlord
            $tenant = CustomTenantModel::create([
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'name_company' => $validated['name_company'],
                'database' => strtolower($validated['domain']),
                'owner_email' => $validated['email'],
                'is_active' => true,
                'expires_at' => now()->addDays(10),
            ]);

            // 2. Crear la base de datos
            $dbName = $tenant->database;
            $dbExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

            if ($dbExists) {
                return response()->json(['message' => 'La base de datos ya existe para este tenant', 'status' => 409], 409);
            }
            
            // se crea la base si no existía
            DB::statement("CREATE DATABASE `{$tenant->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // 3. Ejecutar migraciones en la base del tenant
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            // seeders del nuevo tenant:
            Artisan::call('db:seed', [
                '--database' => 'tenant',
                '--class' => 'TenantSeeder',
                '--force' => true,
            ]);

            // 4. Crear el usuario en la base del tenant
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt(Arr::get($validatedData, 'password')),
            ];
            $userData['activation_code'] = Str::random(30).time();
            $userData['is_active']       = 1;
            $userData['email_verified_at'] = Carbon::now();
            DB::connection('tenant')->table('users')->insert($userData);

            // Asignar el role_id 2 (administrador)
            $userId = DB::connection('tenant')->table('users')->where('email', $validated['email'])->value('id');
            DB::connection('tenant')->table('model_has_roles')->insert([
                'role_id' => 2,
                'model_type' => 'App\\User',
                'model_id' => $userId,
            ]);

            // 5. Asignar módulos seleccionados al tenant
            if (isset($validated['modulos']) && is_array($validated['modulos']) && count($validated['modulos']) > 0) {
                $fechaInicio = Carbon::now();
                
                foreach ($validated['modulos'] as $modulo) {
                    // fecha de vencimiento inicial son 10 dias de prueba o demo
                    $metodoPago = $modulo['metodo_pago'];
                    $fechaVencimiento = now()->addDays(10);
                    
                    DB::connection('landlord')->table('tenant_modulo')->insert([
                        'tenant_id' => $tenant->id,
                        'modulo_id' => $modulo['modulo_id'],
                        'metodo_pago' => $metodoPago,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'is_active' => true,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }                
            } else {
                \Log::warning('No se recibieron módulos para el tenant', ['tenant_id' => $tenant->id]);
            }

            try {
                // Construir URL de acceso al tenant
                $frontUrl = config('services.frontend_protocol') . '://' . $validated['domain'] . '.' . config('services.frontend_domain');
                if (config('services.frontend_port')) {
                    $frontUrl .= ':' . config('services.frontend_port');
                }
                
                // Enviar correo de confirmación al tenant
                Mail::to($validated['email'])->send(new RegistrationSuccessMail(
                    $validated['name'],
                    $validated['name_company'],
                    $frontUrl
                ));
                
                // Enviar correo de notificacion al email corporativo
                $corporateEmail = config('services.corporate_email');
                if ($corporateEmail) {
                    Mail::to($corporateEmail)->send(new RegistrationSuccessMail(
                        $validated['name'],
                        $validated['name_company'],
                        $frontUrl
                    ));
                }
            } catch (\Exception $e) {
                \Log::error('Error enviando email de verificación al tenant: ' . $e->getMessage());
            }

        } catch (\Illuminate\Database\QueryException $exception) {
            // Capturar errores específicos de base de datos
            \Log::error('registerTenant - Error de base de datos', [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage()
            ]);
            
            $errorMessage = 'Error al crear el tenant';
            
            // Detectar errores de duplicado (código 23000)
            if ($exception->getCode() == 23000) {
                if (str_contains($exception->getMessage(), 'owner_email')) {
                    $errorMessage = 'Este email ya está registrado como propietario de otro tenant. Por favor usa otro email.';
                } elseif (str_contains($exception->getMessage(), 'domain')) {
                    $errorMessage = 'Este dominio ya está registrado. Por favor elige otro dominio.';
                } elseif (str_contains($exception->getMessage(), 'name_company')) {
                    $errorMessage = 'Esta empresa ya está registrada. Por favor usa otro nombre de empresa.';
                } else {
                    $errorMessage = 'Los datos ingresados ya están registrados. Por favor verifica e intenta de nuevo.';
                }
            }
            
            return response()->json(['error' => $errorMessage, 'status' => 422], 422);
        } catch (\Exception $exception) {
            \Log::error('registerTenant - Error general', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return response()->json(['error' => $exception->getMessage(), 'status' => 422], 422);
        }

        return response()->json(['success' => 'Usuario registrado con éxito', 'status' => 201], 201);
    }

    /**
     * Create users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(UserStoreRequest $request)
    {
        $recaptchaSiteKey = request('recaptcha_token');
        if (trim($recaptchaSiteKey)) {
            $recaptchaSecret = "6Ld8-IgrAAAAAB1HouiMlq7bDv_r6Ok6XvSauqBO";
            $apiCall = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaSiteKey";
            $response = file_get_contents($apiCall);
            $responseKeys = json_decode($response, true);
            if(intval($responseKeys["success"]) !== 1) {
                return response()->json(['error' => 'Recaptcha no valido'], 401);
            }
        }

        /** @var User $user */
        $validatedData = $request->validated();

        try {
            $validatedData['password']        = bcrypt(Arr::get($validatedData, 'password'));
            $validatedData['activation_code'] = Str::random(30).time();
            $validatedData['photo']           = null;
            // Expiración configurable para el activation_code usando remember_expire
            $activationExpireMinutes = (int)config('services.activation_expire_minutes', 120); // 2 horas por defecto
            $activationExpireText = '2 horas';
            $validatedData['remember_expire'] = Carbon::now()->addMinutes($activationExpireMinutes);
            $user = User::create($validatedData);

            // * si no viene el role, asignamos Role = "Usuario" que es role de usuario normal por defecto *
            DB::table('model_has_roles')->insert([
                'role_id' => 3,
                'model_type' => 'App\User',
                'model_id' => $user->id
            ]);
            
            // Enviar email con Resend API
            try {
                // Obtener el tenant actual de forma segura usando Spatie
                $tenant = \Spatie\Multitenancy\Models\Tenant::current();
                if (!$tenant) {
                    throw new \Exception('No se pudo determinar el tenant actual.');
                }
                $tenantDomain = $tenant->domain;
                $nombreEmpresa = $tenant->name_company ?? 'Empresa';
                $descripcionEmpresa = $tenant->description ?? '';

                $base = config('services.frontend_protocol') . '://' . $tenantDomain . '.' . config('services.frontend_domain');
                if (config('services.frontend_port')) {
                    $base .= ':' . config('services.frontend_port');
                }
                
                $verificationUrl = $base . "/verificar?token=" . $user->activation_code;

                // Enviar el correo
                Mail::to($user->email)->send(new VerifyAccountMail(
                    $user,
                    $nombreEmpresa,
                    $descripcionEmpresa,
                    $verificationUrl,
                    $activationExpireText
                ));
            } catch (\Exception $e) {
                \Log::error('Error enviando email de verificación con Resend: ' . $e->getMessage());
            }

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => $user], 201);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $recaptchaSiteKey = request('recaptcha_token');
        if (trim($recaptchaSiteKey)) {
            $recaptchaSecret = "6Ld8-IgrAAAAAB1HouiMlq7bDv_r6Ok6XvSauqBO";
            $apiCall = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaSiteKey";
            $response = file_get_contents($apiCall);
            $responseKeys = json_decode($response, true);
            if(intval($responseKeys["success"]) !== 1) {
                return response()->json(['error' => 'Recaptcha no valido'], 401);
            }
        }

        $credentials = request(['email', 'password']);

        // Verificar si hay un tenant y si está activo
        $currentTenant = \Spatie\Multitenancy\Models\Tenant::current();
        if ($currentTenant && !$currentTenant->is_active) {
            return response()->json([
                'error' => 'Cuenta suspendida. Contacte al administrador del sistema.',
                'tenant_inactive' => true
            ], 401);
        }

        // Verificar si el usuario existe en la base del tenant solo si hay tenant activo
        $user = null;
        if (function_exists('tenant') && tenant()) {
            try {
                $user = \DB::connection('tenant')->table('users')->where('email', $credentials['email'])->first();
                \Log::info('login: usuario encontrado en tenant', ['user' => $user]);
            } catch (\Exception $e) {
                \Log::error('login: error buscando usuario en tenant', ['error' => $e->getMessage()]);
            }
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Email o contraseña incorrectas'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Verifica el email usando el activation_code enviado por correo
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['error' => 'Token de verificación requerido'], 400);
        }
        $user = \App\User::where('activation_code', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        if ($user->email_verified_at) {
            return response()->json(['error' => 'El email ya ha sido verificado anteriormente'], 400);
        }
        $now = Carbon::now();
        $expire = new Carbon($user->remember_expire);
        if ($now->gt($expire)) {
            return response()->json(['error' => 'El enlace de verificación ha expirado'], 400);
        }
        $user->email_verified_at = $now;
        $user->is_active = 1;
        $user->save();

        // Enviar email de bienvenida
        try {
            // Obtener el tenant actual de forma segura usando Spatie
            $tenant = \Spatie\Multitenancy\Models\Tenant::current();
            if (!$tenant) {
                throw new \Exception('No se pudo determinar el tenant actual.');
            }
            $tenantDomain = $tenant->domain;
            $nombreEmpresa = $tenant->name_company ?? 'Empresa';
            $descripcionEmpresa = $tenant->description ?? '';
            
            $frontUrl = config('services.frontend_protocol') . '://' . $tenantDomain . '.' . config('services.frontend_domain');
            if (config('services.frontend_port')) {
                $frontUrl .= ':' . config('services.frontend_port');
            }

            Mail::to($user->email)->send(new BienvenidaMail(
                $nombreEmpresa,
                $descripcionEmpresa,
                $user->name,
                $frontUrl
            ));
        } catch (\Exception $e) {
            \Log::error('verifyEmail: Error enviando email de bienvenida con Resend', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Email verificado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_active' => $user->is_active
            ]
        ], 200);
    }

    /**
     * Reenviar email de verificación para usuarios no verificados
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        $email = $request->input('email');
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un email válido'
            ], 422);
        }
        $user = \App\User::where('email', $email)->first();
        if (!$user) {
            // Por seguridad, no revelamos si el email existe o no
            return response()->json([
                'success' => true,
                'message' => 'Si el email está registrado y no verificado, recibirás un nuevo correo de verificación'
            ], 200);
        }
        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Este email ya ha sido verificado'
            ], 400);
        }
        // Generar nuevo activation_code y expiración
        $user->activation_code = Str::random(30).time();
        $activationExpireMinutes = (int)config('services.activation_expire_minutes', 120); // 2 horas por defecto
        $user->remember_expire = Carbon::now()->addMinutes($activationExpireMinutes);
        $user->save();
        // Enviar email de verificación
        try {
            // Obtener el tenant actual de forma segura usando Spatie
            $tenant = \Spatie\Multitenancy\Models\Tenant::current();
            if (!$tenant) {
                throw new \Exception('No se pudo determinar el tenant actual.');
            }
            $tenantDomain = $tenant->domain;
            $nombreEmpresa = $tenant->name_company ?? 'Empresa';
            $descripcionEmpresa = $tenant->description ?? '';
            
            $base = config('services.frontend_protocol') . '://' . $tenantDomain . '.' . config('services.frontend_domain');
            if (config('services.frontend_port')) {
                $base .= ':' . config('services.frontend_port');
            }

            $activationExpireText = $activationExpireMinutes >= 1440 ? ($activationExpireMinutes/1440).' días' : $activationExpireMinutes.' minutos';
            $verificationUrl = $base . "/verificar?token=" . $user->activation_code;

            // Enviar el correo
            Mail::to($user->email)->send(new VerifyAccountMail(
                $user,
                $nombreEmpresa,
                $descripcionEmpresa,
                $verificationUrl,
                $activationExpireText
            ));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el email de verificación. Inténtalo más tarde.'
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Nuevo email de verificación enviado exitosamente'
        ], 200);
    }
    
    /**
     * Verifica el estado de un token de verificación sin procesarlo
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkVerificationToken(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json([
                'valid' => false,
                'reason' => 'Token no proporcionado',
                'can_resend' => true
            ], 200);
        }
        $user = \App\User::where('activation_code', $token)->first();
        if (!$user) {
            return response()->json([
                'valid' => false,
                'reason' => 'Usuario no encontrado',
                'can_resend' => false
            ], 200);
        }
        if ($user->email_verified_at) {
            return response()->json([
                'valid' => false,
                'reason' => 'Email ya verificado',
                'can_resend' => false,
                'already_verified' => true,
                'email' => $user->email
            ], 200);
        }
        $now = Carbon::now();
        $expire = new Carbon($user->remember_expire);
        if ($now->gt($expire)) {
            return response()->json([
                'valid' => false,
                'reason' => 'Token expirado o inválido',
                'can_resend' => true,
                'expired' => true,
                'email' => $user->email
            ], 200);
        }
        // Token válido y usuario no verificado
        return response()->json([
            'valid' => true,
            'user_id' => $user->id,
            'email' => $user->email,
            'can_resend' => false
        ], 200);
    }

    /**
     * Solicitar restablecimiento de contraseña
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPasswordRequest(Request $request)
    {
        $email = $request->input('email');
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un email válido'
            ], 422);
        }
        $user = \App\User::where('email', $email)->first();
        if (!$user || !$user->is_active) {
            // Mensaje genérico por seguridad
            return response()->json([
                'success' => true,
                'message' => 'Si el email está registrado, recibirás un correo con instrucciones para restablecer tu contraseña'
            ], 200);
        }
        // Generar token y expiración
        $token = Str::random(24).time();
        $user->remember_token = $token;
        $user->remember_expire = Carbon::now()->addMinutes(60); // 1 hora para reset
        $user->save();
        // Enviar email de recuperación
        try {
            // Obtener el tenant actual de forma segura usando Spatie
            $tenant = \Spatie\Multitenancy\Models\Tenant::current();
            if (!$tenant) {
                throw new \Exception('No se pudo determinar el tenant actual.');
            }
            $tenantDomain = $tenant->domain;
            $nombreEmpresa = $tenant->name_company ?? 'Empresa';
            $descripcionEmpresa = $tenant->description ?? '';
                       
            $base = config('services.frontend_protocol') . '://' . $tenantDomain . '.' . config('services.frontend_domain');
            if (config('services.frontend_port')) {
                $base .= ':' . config('services.frontend_port');
            }
            
            $resetUrl = $base . "/reset-password?token=" . $token;
            
            Mail::to($user->email)->send(
                new ResetPasswordMail($user, $resetUrl, $nombreEmpresa, $descripcionEmpresa)
            );
        } catch (\Exception $e) {
            \Log::error('resetPasswordRequest: Error enviando email de recuperación', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Si el email está registrado, recibirás un correo con instrucciones para restablecer tu contraseña'
        ], 200);
    }

    /**
     * Confirmar restablecimiento de contraseña usando el token enviado por correo
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmResetPassword(Request $request)
    {
        $token = $request->input('token');
        $newPassword = $request->input('new_password');
        if (!$token || !$newPassword) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar token y nueva contraseña'
            ], 422);
        }
        if (strlen($newPassword) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ], 422);
        }
        $user = \App\User::where('remember_token', $token)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token de restablecimiento inválido o expirado'
            ], 400);
        }
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 400);
        }
        $now = Carbon::now();
        $expire = new Carbon($user->remember_expire);
        if ($now->gt($expire)) {
            return response()->json([
                'success' => false,
                'message' => 'El enlace de restablecimiento ha expirado'
            ], 400);
        }
        $user->password = Hash::make($newPassword);
        $user->remember_token = null;
        $user->remember_expire = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.'
        ], 200);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        // Obtener el token desde el header Authorization o la cookie access_token
        $token = request()->bearerToken();
        if (!$token) {
            $token = request()->cookie('access_token');
        }

        if ($token) {
            auth('api')->setToken($token)->logout();
        }

        // Eliminar la cookie access_token usando el mismo dominio y secure que respondWithToken
        $cookieDomain = config('services.frontend_cookie_domain', '127.0.0.1');
        $cookieSecure = config('services.frontend_cookie_secure', false);
        $cookie = \Cookie::forget('access_token', '/', $cookieDomain, $cookieSecure, true, false, 'lax');

        return response()->json(['message' => 'Sesión cerrada correctamente'])->withCookie($cookie);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    public function guard() {
        return \Auth::Guard('api');
    }
}
