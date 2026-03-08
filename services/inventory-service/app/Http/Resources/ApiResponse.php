<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;

/**
 * Standardised API response builder ensuring consistent envelope format:
 * { success, message, data, meta }
 */
final class ApiResponse
{
    public static function success(
        mixed   $data    = null,
        string  $message = 'Operation successful.',
        int     $status  = 200,
        array   $meta    = []
    ): JsonResponse {
        $body = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $body['data'] = $data;
        }

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function paginated(mixed $paginator, string $message = 'Data retrieved successfully.'): JsonResponse
    {
        if (method_exists($paginator, 'toArray')) {
            $result = $paginator->toArray();
            return self::success(
                data:    $result['data'],
                message: $message,
                meta:    [
                    'current_page' => $result['current_page']  ?? null,
                    'per_page'     => $result['per_page']       ?? null,
                    'total'        => $result['total']          ?? null,
                    'last_page'    => $result['last_page']      ?? null,
                    'from'         => $result['from']           ?? null,
                    'to'           => $result['to']             ?? null,
                ]
            );
        }

        return self::success($paginator, $message);
    }

    public static function error(
        string $message = 'An error occurred.',
        int    $status  = 500,
        array  $errors  = []
    ): JsonResponse {
        $body = ['success' => false, 'message' => $message];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    public static function created(mixed $data = null, string $message = 'Resource created successfully.'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(string $message = 'Resource deleted successfully.'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], 200);
    }
}
