<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
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

        $this->renderable(function (Throwable $e, Request $request) {
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'status' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                    'errors' => $this->isValidationException($e) ? $e->errors() : [],
                ],
            ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        });
    }

    /**
     * Determine if exception is a validation exception.
     */
    protected function isValidationException(Throwable $e): bool
    {
        return $e instanceof ValidationException;
    }
}
