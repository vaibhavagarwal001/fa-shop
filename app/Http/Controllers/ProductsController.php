<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bakingo\Nodes;
use Illuminate\Support\Facades\DB;
class ProductsController extends Controller
{
    //

    /**
     * SELECT n.nid, n.type, cp.product_id, cp.sku, n.title, flt.field_long_title_value AS long_title, ua.alias, (cprice.commerce_price_amount / 100) AS amount 
FROM node n 
INNER JOIN field_data_field_product fdfp ON fdfp.entity_id = n.nid 
INNER JOIN commerce_product cp ON fdfp.field_product_product_id = cp.product_id 
LEFT JOIN field_data_commerce_price cprice ON cprice.entity_id = cp.product_id 
INNER JOIN field_data_field_long_title flt ON n.nid = flt.entity_id 
INNER JOIN field_data_field_mini_description fmd ON n.nid = fmd.entity_id 
INNER JOIN url_alias AS ua ON CONCAT('node/', n.nid) = ua.source 
WHERE 
n.type IN ('regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon') 
AND n.status = 1 

GROUP BY cp.product_id ORDER BY n.nid DESC limit 10;
     * 
     * 
     */

    public function getlisting(){
        $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));

        $Nodes = Nodes::join('field_data_field_product' , function($join){
            $join->on('field_data_field_product.entity_id', '=', 'node.nid'); 
        })->join('commerce_product' , function($join){
            $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
        })->leftjoin('field_data_commerce_price' , function($join){
            $join->on('field_data_commerce_price.entity_id', '=', 'commerce_product.product_id');
        })->join('field_data_field_long_title' , function($join){
            $join->on('node.nid', '=', 'field_data_field_long_title.entity_id');
        })->join('field_data_field_mini_description' , function($join){
            $join->on('node.nid', '=', 'field_data_field_mini_description.entity_id');
        })->join('url_alias' , function($join){
            $join->on(DB::raw("CONCAT('node/', node.nid)"), '=', 'url_alias.source');
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"]
        ])->groupBy("commerce_product.product_id")
        ->select("node.nid", "node.type", "commerce_product.product_id", "commerce_product.sku", "node.title", DB::raw("field_data_field_long_title.field_long_title_value AS long_title"), "url_alias.alias", DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount") , 'field_data_field_mini_description.field_mini_description_value')
        ->orderBy("node.nid" , "asc")
        ->limit(10)
        ->get();
        // print_r($Nodes);
        // exit;
        if($Nodes->first()){
            $response = [];
            foreach($Nodes as $node){
                $response[] = [
                    "node_id" => $node->nid,
                    "product_type" => $node->type,
                    "product_id" => $node->product_id,
                    "sku" => $node->sku,
                    "title" => $node->title,
                    "long_title" => $node->long_title,
                    "url" => $node->alias,
                    "amount" => $node->amount,
                    "mini_desc" => $node->field_mini_description_value
                ];
            }
        }
        return response()->json($response);
    }
}
