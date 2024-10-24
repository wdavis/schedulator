<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('v1/*')) {

                $message = $e->getMessage();

                $status = 500;
                if($e instanceof HttpStatusContract) {
                    $status = $e->getHttpStatusCode();
                }

                if($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $status = 404;
                    if (preg_match('/No query results for model \[App\\\Models\\\(.*?)]\./', $e->getMessage(), $matches)) {
                        $modelName = $matches[1]; // This will contain the model name e.g. 'Booking'
                        $message = "$modelName not found.";
                    }
                }

                if($e instanceof QueryException) {
                    $status = 400;
                    $message = 'Bad request.';
                }

                if($e instanceof ThrottleRequestsException) {
                    $status = 429;
                    $message = 'Too many requests.';
                }

                return response()->json([
                    'message' => $message
                ], $status);
            }

            return parent::render($request, $e);
        });

    }
}
