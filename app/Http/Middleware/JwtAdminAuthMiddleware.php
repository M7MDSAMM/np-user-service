<?php

namespace App\Http\Middleware;

use App\Domain\Auth\InvalidTokenException;
use App\Domain\Auth\JwtTokenServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAdminAuthMiddleware
{
    public function __construct(
        private JwtTokenServiceInterface $tokenService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized($request, 'Missing or malformed Authorization header');
        }

        $token = substr($header, 7);

        try {
            $claims = $this->tokenService->validateToken($token);
        } catch (InvalidTokenException $e) {
            return $this->unauthorized($request, $e->getMessage());
        }

        if (($claims['typ'] ?? '') !== 'admin') {
            return $this->unauthorized($request, 'Token type is not admin');
        }

        $request->attributes->set('auth_admin_uuid', $claims['sub']);
        $request->attributes->set('auth_admin_role', $claims['role'] ?? null);

        return $next($request);
    }

    private function unauthorized(Request $request, string $message): Response
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'status'  => 401,
            ],
            'correlation_id' => $request->header('X-Correlation-Id'),
        ], 401);
    }
}
