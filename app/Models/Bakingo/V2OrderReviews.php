<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class V2OrderReviews extends BKModel
{
    //
    protected $table = 'v2_order_reviews';

    protected $primaryKey = 'rid';

    public function getCount($cityName){
        $V2OrderReviews = $this->where([
            ["status" ,  "=" , 1 ],
            ["city" , "=" , $cityName]
        ])
        ->select("author", "city", "order_rating", "review_title", "review_text", "created")
        ->get()->count();
        return $V2OrderReviews;
    }
    public function getData($cityName , $limit , $offset){
        $V2OrderReviews = $this->where([
            ["status" ,  "=" , 1 ],
            ["city" , "=" , $cityName]
        ])
        ->select("author", "city", "order_rating", "review_title", "review_text", "created")
        ->limit($limit)
        ->offset($offset)
        ->get();

        if($V2OrderReviews->first()){
            return $V2OrderReviews;
        }
        return [];
    }

}
