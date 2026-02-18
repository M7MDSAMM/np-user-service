<?php

use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\JwtAdminAuthMiddleware;
use App\Http\Middleware\RequestTimingMiddleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationIdMiddleware::class);
        $middleware->append(RequestTimingMiddleware::class);

        $middleware->alias([
            'jwt.admin'    => JwtAdminAuthMiddleware::class,
            'role.super'   => \App\Http\Middleware\RequireSuperAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::validation(
                    $e->errors(),
                    $e->getMessage(),
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::notFound(
                    $e->getMessage() ?: 'The requested resource was not found.',
                );
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::forbidden(
                    $e->getMessage() ?: 'You do not have permission to perform this action.',
                );
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                if ($status >= 500) {
                    return ApiResponse::serverError(
                        app()->hasDebugModeEnabled() ? $e->getMessage() : 'Internal server error.',
                    );
                }

                return ApiResponse::error(
                    $e->getMessage() ?: 'An error occurred.',
                    'ERROR',
                    $status,
                );
            }
        });
    })->create();
