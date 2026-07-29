<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Assign a traceable identifier to an API request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->validRequestId($request->header('X-Request-Id'))
            ?? (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function validRequestId(?string $requestId): ?string
    {
        if ($requestId === null) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $requestId) === 1
            ? $requestId
            : null;
    }
}
