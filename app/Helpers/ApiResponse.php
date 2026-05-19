<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, string $message = 'OK', int $status = 200)
    {
        $responseData = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ];

        if ($data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $responseData['data'] = $data->items();
            $responseData['meta'] = [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ];
        }

        return response()->json($responseData, $status);
    }

    public static function error(string $message = 'Error', $errors = null, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $status);
    }
}
