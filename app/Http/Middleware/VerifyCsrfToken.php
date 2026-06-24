<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    protected $except = [
        'idanalyzer/callback',
        'api/*',
        // Exclude this route from CSRF protection
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
