<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperUserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && strtolower(Auth::user()->username) === 'awais') {
            return $next($request);
        }

        // Return a forbidden page or redirect back with an error
        abort(403, 'Unauthorized. Only superuser Awais has access to this page.');
    }
}
