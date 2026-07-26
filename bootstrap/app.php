<?php

use App\Http\Middleware\AdicionarCabecalhosSeguranca;
use App\Http\Middleware\ExigirPermissao;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permissao' => ExigirPermissao::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AdicionarCabecalhosSeguranca::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $exception) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response): Response {
            $request = request();
            $status = $response->getStatusCode();

            if (! $request->expectsJson() && in_array($status, [403, 404, 419, 422], true)) {
                return Inertia::render('Erro', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
