<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTimingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $latencyMs = (microtime(true) - $started) * 1000;

        Log::info('request.completed', [
            'service'       => env('SERVICE_NAME', config('app.name', 'user-service')),
            'method'        => $request->getMethod(),
            'route'         => $request->path(),
            'status_code'   => $response->getStatusCode(),
            'latency_ms'    => round($latencyMs, 2),
            'correlation_id'=> $request->header('X-Correlation-Id', ''),
            'actor'         => $request->attributes->get('auth_admin_uuid'),
        ]);

        return $response;
    }
}
