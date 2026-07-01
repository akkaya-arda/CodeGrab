<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!file_exists(storage_path('installed'))) {
            if (!$request->is('/') && !$request->is('install') && !$request->is('install/*')) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'App not configured'
                    ], 503);
                }
                abort(503, 'App not configured');
            }
        }

        return $next($request);
    }
}
