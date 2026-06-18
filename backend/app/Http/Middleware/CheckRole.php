<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check() || !auth()->user()->role) {
            abort(403, 'Unauthorized action. No role assigned.');
        }

        $userRole = auth()->user()->role->name;
        
        // Admin has universal access
        if (!in_array($userRole, $roles) && $userRole !== 'Admin') {
            abort(403, 'Unauthorized action. You do not have the required role.');
        }

        return $next($request);
    }
}
