<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Documentation",
 *     description="Documentación de API Saas"
 * ),
 * @OA\Server(
 *     url="/api",
 *     description="API base path"
 * )
 */
abstract class Controller extends BaseController
{
    //
}