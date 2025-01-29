<?php

use App\Contracts\HttpStatusContract;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->throttleApi();

        $middleware->alias([
            'api-key' => \App\Http\Middleware\ValidateApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (Throwable $e) {
            //
        });

        $exceptions->renderable(function (Throwable $e, Request $request) use ($exceptions) {
            if ($request->is('v1/*')) {

                $message = $e->getMessage();

                $status = 500;
                if ($e instanceof HttpStatusContract) {
                    $status = $e->getHttpStatusCode();
                }

                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $status = 404;
                    if (preg_match('/No query results for model \[App\\\Models\\\(.*?)]\./', $e->getMessage(), $matches)) {
                        $modelName = $matches[1]; // This will contain the model name e.g. 'Booking'
                        $message = "$modelName not found.";
                    }
                }

                if ($e instanceof QueryException) {
                    $status = 400;
                    $message = 'Bad request.';
                }

                if ($e instanceof ThrottleRequestsException) {
                    $status = 429;
                    $message = 'Too many requests.';
                }

                return response()->json([
                    'message' => $message,
                ], $status);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        });
    })->create();
