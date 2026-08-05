<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->statefulApi();

        // CORS — permitir requests desde el frontend en Railway
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Registro automático en el sistema de logs para cualquier excepción
        $exceptions->reportable(function (\Throwable $e) {
            \App\Services\ErrorLoggerService::logException($e);
        });

        // Respuesta limpia y segura al cliente sin exponer información sensible ni stack traces
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $status = $response->getStatusCode();

                // Evitar sobreescribir validaciones 422 normales de Laravel si ya contienen errores estructurados
                if ($status === 422) {
                    return $response;
                }

                $message = match ($status) {
                    404 => 'Recurso o endpoint no encontrado.',
                    403 => 'Acceso no autorizado.',
                    401 => 'No autenticado.',
                    502, 503 => 'El servicio no está disponible temporalmente. Intente más tarde.',
                    default => 'Ha ocurrido un error interno en el servidor.',
                };

                return response()->json([
                    'success' => false,
                    'status'  => $status,
                    'error'   => $message,
                ], $status);
            }

            return $response;
        });
    })->create();
