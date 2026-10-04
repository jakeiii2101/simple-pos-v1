<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->isPlatformOwner()) {
            return $next($request);
        }

        $account = $user->account;

        if ($account === null || $account->status !== Account::STATUS_ACTIVE) {
            abort(403, 'This SniperPOS business account is not active.');
        }

        return $next($request);
    }
}
