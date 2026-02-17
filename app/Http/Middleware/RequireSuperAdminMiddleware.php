<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('auth_admin_role') !== 'super_admin') {
            return ApiResponse::forbidden('Super Admin access required.', 'FORBIDDEN');
        }

        return $next($request);
    }
}
