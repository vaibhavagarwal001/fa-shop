<?php

namespace App\Exceptions;

use App\Http\Controllers\Component\ResponseComponent;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        $responseController = new ResponseComponent(); 
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            $response = $responseController->error('Resource / Model not found');
            return response()->json($response, 404);
        }
        
        if($exception instanceof HttpException && $request->wantsJson()){
            $response = $responseController->error('Invalid URL');
            return response()->json($response, 404);
        }
        
        return parent::render($request, $exception);
    }
}
