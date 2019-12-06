<?php

namespace App\Http\Controllers\Component;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResponseComponent extends Controller
{
    //
    public function error($messages){
        Log::channel('daily')->error($messages); // Success Log
        return [
            'error' => true,
            'error_message' => $messages,
            'data' => [],
            'success' => false,
            'status_code' => 400
        ];
    }

    public function success($messages , $data , $meta = null){
        $response =  [
            'error' => false,
            'message' => $messages,
            'data' => $data,
            'success' => true,
            'status_code' => 200
        ];
        if($meta){
            $response['_meta'] =  $meta;
        }
        Log::channel('daily')->info("Response Message:".json_encode($response)); // Success Log
        return $response;
    }

    public function exception($messages){
        Log::channel('daily')->error("Exception:".$messages); // Success Log
        return [
            'error' => true,
            'error_message' => $messages,
            'data' => [],
            'success' => false,
            'status_code' => 500
        ];
    }
}
