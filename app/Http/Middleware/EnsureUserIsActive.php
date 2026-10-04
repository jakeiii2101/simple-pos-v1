<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isActive()) {
            abort(403);
        }

        if ($user->business !== null && ! $user->business->isActive()) {
            abort(403, 'This business workspace is currently suspended.');
        }

        return $next($request);
    }
}
