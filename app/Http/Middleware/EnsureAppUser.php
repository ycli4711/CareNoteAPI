<?php

namespace App\Http\Middleware;

use App\Exceptions\Auth\AccountDisabledException;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppUser
{
    /**
     * Ensure an API token belongs to a client application user.
     *
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            throw new AuthenticationException;
        }

        if ($request->user()->status !== 'active') {
            throw new AccountDisabledException;
        }

        return $next($request);
    }
}
