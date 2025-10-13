<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GzipMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Hanya kompres kalau client support gzip
        if (strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
            $content = $response->getContent();
            $response->setContent(gzencode($content, 9));
            $response->headers->set('Content-Encoding', 'gzip');
            $response->headers->set('Vary', 'Accept-Encoding');
            $response->headers->set('Content-Length', strlen($response->getContent()));
        }

        return $response;
    }
}
