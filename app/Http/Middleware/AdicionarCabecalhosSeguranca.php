<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdicionarCabecalhosSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cache-Control', 'no-store, private');

        $viteHttp = 'http://localhost:5174';
        $viteWs = 'ws://localhost:5174';
        $script = app()->isLocal()
            ? "'self' 'unsafe-inline' 'unsafe-eval' {$viteHttp}"
            : "'self'";
        $style = app()->isLocal()
            ? "'self' 'unsafe-inline' {$viteHttp}"
            : "'self' 'unsafe-inline'";
        $connect = app()->isLocal()
            ? "'self' {$viteHttp} {$viteWs}"
            : "'self'";

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src {$script}; style-src {$style}; img-src 'self' data:; font-src 'self' data:; connect-src {$connect}; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
        );

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
