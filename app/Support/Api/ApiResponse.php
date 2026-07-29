<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiResponse
{
    /**
     * Create a successful API response.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data,
        array $meta = [],
        int $status = 200,
        ?Request $request = null,
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'meta' => self::meta($request, $meta),
        ], $status);
    }

    /**
     * Create a failed API response.
     *
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        ApiErrorCode $code,
        int $status,
        array $errors = [],
        array $meta = [],
        ?Request $request = null,
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'code' => $code->value,
            'errors' => (object) $errors,
            'meta' => self::meta($request, $meta),
        ], $status);
    }

    /**
     * Build response metadata.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private static function meta(?Request $request, array $meta): array
    {
        $request ??= request();

        return [
            'request_id' => $request->attributes->get('request_id'),
            ...$meta,
        ];
    }
}
