<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
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

        $this->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($request->expectsJson()) {
                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof ModelNotFoundException => 404,
                    $e instanceof NotFoundHttpException => 404,
                    $e instanceof ValidationException => 422,
                    $e instanceof ThrottleRequestsException => 429,
                    default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                };

                $message = match (true) {
                    $e instanceof ValidationException => $e->getMessage(),
                    $e instanceof AuthenticationException => 'No autenticado.',
                    $e instanceof AuthorizationException => 'No autorizado.',
                    $e instanceof ModelNotFoundException => 'Recurso no encontrado.',
                    $e instanceof ThrottleRequestsException => 'Demasiadas solicitudes.',
                    $e instanceof NotFoundHttpException => 'Ruta no encontrada.',
                    default => config('app.debug') && app()->isLocal() ? $e->getMessage() : 'Error interno del servidor.',
                };

                $data = $e instanceof ValidationException ? $e->errors() : null;

                return response()->json([
                    'success' => false,
                    'data' => $data,
                    'message' => $message,
                    'status' => $status,
                ], $status);
            }

            return parent::render($request, $e);
        });
    }
}
