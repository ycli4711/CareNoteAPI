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
        ApiSuccessCode $code = ApiSuccessCode::Ok,
        ?string $message = null,
        ?Request $request = null,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'code' => $code->value,
            'message' => $message ?? $code->message(),
            'data' => $data,
            'errors' => (object) [],
            'meta' => self::meta($request, $meta),
        ], $code->status());
    }

    /**
     * Create a failed API response.
     *
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        ApiErrorCode $code,
        array $errors = [],
        array $meta = [],
        ?string $message = null,
        ?int $status = null,
        ?Request $request = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'code' => $code->value,
            'message' => $message ?? $code->message(),
            'data' => null,
            'errors' => (object) $errors,
            'meta' => self::meta($request, $meta),
        ], $status ?? $code->status());
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
