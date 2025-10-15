<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * Response error standar
     *
     * @param string $message
     * @param string $error
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse(string $message, $error, int $code = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code'    => $code,
            'message' => $message,
            'error'   => $error
        ], $code);
    }

    /**
     * Response sukses standar
     *
     * @param string $message
     * @param mixed $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse(
        string $message,
        $data = [],
        int $code = 200,
        bool $serverPaging = false,
        $paginator = null
    ): JsonResponse {
        if ($serverPaging && $paginator instanceof LengthAwarePaginator) {
            $response = [
                'success'       => true,
                'code'          => $code,
                'message'       => $message,
                'data'          => $data->items(),
                'server_paging' => true,
                'meta'          => [
                    'current_page'    => $paginator->currentPage(),
                    'first_page_url'  => $paginator->url(1),
                    'from'            => $paginator->firstItem(),
                    'last_page'       => $paginator->lastPage(),
                    'last_page_url'   => $paginator->url($paginator->lastPage()),
                    'links'           => $paginator->linkCollection(),
                    'next_page_url'   => $paginator->nextPageUrl(),
                    'path'            => $paginator->path(),
                    'per_page'        => $paginator->perPage(),
                    'prev_page_url'   => $paginator->previousPageUrl(),
                    'to'              => $paginator->lastItem(),
                    'total'           => $paginator->total(),
                ],
            ];
        } else {
            $response = [
                'success'       => true,
                'code'          => $code,
                'message'       => $message,
                'data'          => $data,
                'server_paging' => $serverPaging,
                'meta'          => [
                    'current_page'    => 1,
                    'first_page_url'  => null,
                    'from'            => 1,
                    'last_page'       => 1,
                    'last_page_url'   => null,
                    'links'           => [],
                    'next_page_url'   => null,
                    'path'            => request()->url(),
                    'per_page'        => is_countable($data) ? count($data) : 0,
                    'prev_page_url'   => null,
                    'to'              => is_countable($data) ? count($data) : 0,
                    'total'           => is_countable($data) ? count($data) : 0,
                ],
            ];
        }

        return response()->json($response, $code);
    }
}
