<?php

namespace App\Http\Controllers\Bakingo;

use App\Http\Controllers\Component\ResponseComponent;
use App\Http\Controllers\Controller;
use App\Models\Bakingo\V2OrderReviews;
use Exception;
use Illuminate\Http\Request;
/**
 * Class Name   : ReviewAndRatingController
 * Author       : Vaibhav Agarwal <vaibhav.agarwal@bakingo.com>
 * Created      : 12 - Dec - 2019
 * Description  : This will request and response from the 
 *                  rating and Review of the bakingo database 
 */
class ReviewAndRatingController extends Controller
{
    // This will help in sending the response
    protected $ResponseComponent;
    public function __construct() {
        $this->ResponseComponent = new ResponseComponent();

    }

    public function getReviews(Request $request,$cityName = ""){
        try{
            $requestData = $request->all();
            $currentPage = isset($requestData['current_page']) ? $requestData['current_page'] : 1;
            
            if(!empty($cityName)){
                $limit = 10;
                $offset = ($currentPage - 1) * $limit;
                $V2OrderReviews = new V2OrderReviews();
                $totalCount = $V2OrderReviews->getCount($cityName);
                $data = $V2OrderReviews->getData($cityName , $limit , $offset);
                $totalPages = ceil($totalCount / $limit); 
                $pagination = [
                    'current' => (int) $currentPage,
                    'total_pages' =>  (int) $totalPages,
                    'total_records' =>  (int) $totalCount,
                    'item_per_page' => (int) $limit
                ];
                if(!empty($data )){ $message = "Review Found"; } else {$message = "Reviews not found";}
                $response = $this->ResponseComponent->success($message , $data , $pagination);
            }else{
                $response = $this->ResponseComponent->error("City Name not found");
            }
        }catch(Exception $e){
            $response = $this->ResponseComponent->exception($e->getMessage());
        }

        return response()->json($response);
    }
}
