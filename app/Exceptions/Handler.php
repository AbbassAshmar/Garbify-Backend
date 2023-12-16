<?php

namespace App\Exceptions;

use App\Http\Controllers\HelperController;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Validation\ValidationException;

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

        // for validation failure 
        $this->renderable(function(ValidationException $e, Request $request){
            $errors = $e->errors();
            $fields = array_keys($errors);
            $error = [
                'message' => 'Validation error.',
                'code' => 400,
                'details' => $errors
            ];
        
            //remove "" from validation
            foreach($error['details'] as $key=>$arr){
                for ($i=0 ; $i<count($arr); $i++){
                    if ($arr[$i] === ""){
                        unset($arr[$i]);
                    }
                }
                if (count($arr) == 0 ){
                    unset($error['details'][$key]);
                }
            }

            $metadata = ['error_fields' => $fields];
            $response_body = HelperController::getFailedResponse($error,$metadata);
            return response($response_body, 400);
        });

        // for splatije permission errors UnauthorizedException
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

        // for transaction failure exceptions 
        $this->renderable(function(ResourceNotFoundException $e, Request $request){
            $error = [
                "message"=>$e->getMessage(),
                'code'=>404
            ];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,404);
        });

        // for transaction failure exceptions 
        $this->renderable(function(ProductOutOfStockException $e, Request $request){
            $error = [
                "message"=>$e->getMessage(),
                'code'=>400
            ];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,400);
        });

        



    }

    
}
