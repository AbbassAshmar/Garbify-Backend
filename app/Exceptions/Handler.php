<?php

namespace App\Exceptions;

use App\Http\Controllers\HelperController;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Request;


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

        // for splatie permission errors UnauthorizedException
        $this->renderable(function(UnauthorizedException $e, Request $request){
            $error = [
                'message'=>'You do not have the required authorization.',
                'code' => 403
            ];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 403);
        });

        // for transaction failure exceptions 
        $this->renderable(function(TransactionFailedException $e, Request $request){
            $error = [
                'message' => $e->getMessage(),
                'code' => 500
            ];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body, 500);
        });
    }

    
}
