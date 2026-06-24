<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VeriffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->session()->has('veriff_active_person') && $request->session()->get('veriff_active_person') != null){
            return $next($request);
        }
        return redirect()->route('idanalyzer.error_page')->with('error', 'ID Analyzer verification required. Please try again.');
    }
}
