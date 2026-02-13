<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle validation exceptions
        $this->renderable(function (ValidationException $e, Request $request) {
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'status' => $e->status,
                    'errors' => $e->errors(),
                ],
            ], $e->status);
        });

        // Handle all other exceptions (except auth, which is handled in bootstrap/app.php)
        $this->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException) {
                return;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'status' => $status,
                ],
            ], $status);
        });
    }
}
