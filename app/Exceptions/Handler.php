<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Webklex\PHPIMAP\Exceptions\ResponseException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
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

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {

        if ($exception instanceof ValidationException) {

            return response([
                'message' => $exception->validator->errors()->first(),
            ], 422);
        }
        if ($exception instanceof RoleDoesNotExist) {

            return response([
                'message' => $exception->getMessage()
            ], 404);
        }
        if ($exception instanceof UnauthorizedException) {

            return response([
                'message' => "Vous n'este pas autorisé à effectué cette action",
            ], 403);
        }
        if ($exception instanceof NotFoundHttpException) {

            return response([
                'message' => "Url introuvable."
            ], 404);
        }
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return response([
                'message' => "Non authentifié"
            ], 401);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response([
                'message' => "Aucune instance du model {$exception->getModel()} ne correspond à l'id fourni "
            ], 404);
        }
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response([
                'message' => "Invalide verbe HTTP"
            ], 405);
        }
        if ($exception instanceof FileException) {

            return response([
                'message' => $exception->getMessage()
            ], 400);
        }

        if ($exception instanceof ResponseException) {
            return response([
                'message' => "MAIL INTROUVABLE POUR CET ID"
            ], 400);
        }
        $rendered = parent::render($request, $exception);

        return response([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ], $rendered->getStatusCode());
    }
}
