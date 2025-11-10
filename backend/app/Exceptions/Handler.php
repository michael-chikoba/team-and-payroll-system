<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    private function handleApiException($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'success' => false
            ], 401);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
                'success' => false
            ], 422);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found.',
                'success' => false
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Endpoint not found.',
                'success' => false
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Method not allowed for this endpoint.',
                'success' => false
            ], 405);
        }

        // Default error response
        $statusCode = method_exists($exception, 'getStatusCode') 
            ? $exception->getStatusCode() 
            : 500;

        $response = [
            'message' => $exception->getMessage(),
            'success' => false
        ];

        if (config('app.debug')) {
            $response['trace'] = $exception->getTrace();
            $response['code'] = $exception->getCode();
        }

        return response()->json($response, $statusCode);
    }
}