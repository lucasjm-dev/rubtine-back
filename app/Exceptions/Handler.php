<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    // customize
    public function render($request, Throwable $e)
    {
        // 🔹 Validation errors (422)
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                'validation_error',
                $e->errors(),
                422
            );
        }

        // 🔹 Model not found (404)
        if ($e instanceof ModelNotFoundException) {
            $modelClass = $e->getModel();
            $modelName = strtolower(class_basename($modelClass));
            return ApiResponse::error(
                "{$modelName}_not_found",
                null,
                404
            );
        }


        // 🔹 Route not found (404)
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::error(
                'endpoint_not_found',
                null,
                404
            );
        }

        // 🔹 Unauthorized (401)
        if ($e instanceof UnauthorizedHttpException) {
            return ApiResponse::error(
                'unauthorized',
                null,
                401
            );
        }

        // 🔹 Forbidden (403)
        if ($e instanceof AccessDeniedException) {
            return ApiResponse::error(
                'forbidden',
                null,
                403
            );
        }

        // 🔹 Exception no route [login]
        if ($e instanceof RouteNotFoundException) {
            return ApiResponse::error(
                'login_invalid',
                null,
                401
            );
        }

        // 🔹 Default (500)
        return ApiResponse::error(
            'internal_error',
            ['exception' => $e->getMessage(), 'string' => $e->getTraceAsString()],
            500
        );
    }
}
