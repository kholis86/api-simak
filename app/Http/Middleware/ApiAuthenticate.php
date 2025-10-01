<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use App\Traits\ApiResponseTrait;

class ApiAuthenticate
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenString = $request->bearerToken();

        if (! $tokenString) {
            return $this->errorResponse('Authentication token not provided', 'No bearer token found', 401);
        }

        $token = PersonalAccessToken::findToken($tokenString);

        if (! $token) {
            return $this->errorResponse('Invalid authentication token', 'Token does not exist or is invalid', 401);
        }

        // cek expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return $this->errorResponse('Authentication token has expired', 'Token expired', 401);
        }

        // set user
        $request->setUserResolver(fn() => $token->tokenable);

        return $next($request);
    }
}
