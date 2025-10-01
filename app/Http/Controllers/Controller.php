<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Sistem Akademik API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk login, student, dan master data"
 * )
 *
 * @OA\Server(
 *     url="http://localhost",
 *     description="Local server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
