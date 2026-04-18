<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Exception  $exception
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        $response = null;

        // Manejar ValidationException para retornar todos los errores
        if ($exception instanceof ValidationException) {
            $response = response()->json([
                'errors' => $exception->errors(),
                'status' => 422,
            ], 422);
        } elseif ($exception instanceof UnauthorizedException) {
            $response = response()->json(['error' => 'User have not permission for this page access.'], 401);
        } elseif ($exception instanceof ModelNotFoundException) {
            $response = response()->json(['error' => 'Entry for '.str_replace('App\\', '', $exception->getModel()).' not found'], 404);
        } elseif ($exception instanceof GithubAPIException) {
            $response = response()->json(['error' => $exception->getMessage()], 500);
        } elseif ($exception instanceof RequestException) {
            $response = response()->json(['error' => 'External API call failed.'], 500);
        } elseif ($exception instanceof NotFoundHttpException) {
            $response = response()->json(['error' => 'Endpoint not found'], 404);
        } else {
            $response = parent::render($request, $exception);
        }

        // Agregar headers CORS a todas las respuestas de error
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Add CORS headers to response
     *
     * @param  Request  $request
     * @param  Response  $response
     * @return Response
     */
    protected function addCorsHeaders($request, $response)
    {
        $origin = $request->header('Origin');

        $allowedOrigins = [
            'http://localhost:9000',
            'http://cliente1.template.local:9000',
            'http://template.local:9000',
        ];

        $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : 'http://localhost:9000';

        return $response
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With')
            ->header('Access-Control-Allow-Credentials', 'true');
    }
}
