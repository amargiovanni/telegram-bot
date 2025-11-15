<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $statusCode = $this->getStatusCode($e);

                return response()->json(
                    data: $this->formatApiError($e, $statusCode),
                    status: $statusCode);
            }

            return null;
        });

    }

    /**
     * Determine HTTP code status for the exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // Default fallback
        return 500;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatApiError(Throwable $e, int $statusCode): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $statusCode,
                'key' => class_basename($e),
                'message' => $e->getMessage(),
            ],
        ];
    }
}
