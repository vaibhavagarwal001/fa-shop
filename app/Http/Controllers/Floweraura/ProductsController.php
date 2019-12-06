<?php

namespace App\Http\Controllers\Floweraura;

use Illuminate\Http\Request;
use App\Models\Bakingo\Nodes;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Component\ResponseComponent;
use Exception;
use Illuminate\Support\Facades\Config;

class ProductsController extends Controller
{


    protected $ResponseComponent;


    public function __construct(){
        $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
        $this->ResponseComponent = new ResponseComponent();
    }


    //
    /**
     * 
     * 
     * 
     */
    public function getListingCount()
    {
        $Nodes = Nodes::join('field_data_field_product', function ($join) {
                $join->on('field_data_field_product.entity_id', '=', 'node.nid');
            })->join('commerce_product', function ($join) {
                $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
            })->join('field_data_field_long_title', function ($join) {
                $join->on('node.nid', '=', 'field_data_field_long_title.entity_id');
            })->join('field_data_field_mini_description', function ($join) {
                $join->on('node.nid', '=', 'field_data_field_mini_description.entity_id');
            })
            ->select("node.nid")
            ->whereIn("node.type", ['regular_cake', 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
            ->where([
                ["node.status", "=", "1"],
                ["field_data_field_product.delta", "=", "0"]
            ])->groupBy("node.nid")->get()->count();
        return $Nodes;
    }

    /**
     * 
     * 
     */
    public function getlisting($currentPage = 1)
    {
        try {
            $totalRecords = $this->getListingCount();
            $limit = 10;
            $offset = ($currentPage - 1) * $limit;
            // $offset = 0;
            $totalPages = ceil($totalRecords / $limit);

            $Nodes = Nodes::join('field_data_field_product', function ($join) {
                    $join->on('field_data_field_product.entity_id', '=', 'node.nid');
                })->join('commerce_product', function ($join) {
                    $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
                })->leftjoin('field_data_commerce_price', function ($join) {
                    $join->on('field_data_commerce_price.entity_id', '=', 'commerce_product.product_id');
                })->leftjoin('field_data_field_sell_price', function ($join) {
                    $join->on('field_data_field_sell_price.entity_id', '=', 'commerce_product.product_id');
                })->join('field_data_field_long_title', function ($join) {
                    $join->on('node.nid', '=', 'field_data_field_long_title.entity_id');
                })->join('field_data_field_mini_description', function ($join) {
                    $join->on('node.nid', '=', 'field_data_field_mini_description.entity_id');
                })->join('url_alias', function ($join) {
                    $join->on(DB::raw("CONCAT('node/', node.nid)"), '=', 'url_alias.source');
                })->join('field_data_field_images', function ($join) {
                    $join->on('field_data_field_images.entity_id', '=', 'node.nid');
                })->join('file_managed', function ($join) {
                    $join->on('file_managed.fid', '=', 'field_data_field_images.field_images_fid');
                })
                ->whereIn("node.type", ['regular_cake', 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
                ->where([
                    ["node.status", "=", "1"],
                    ["field_data_field_images.delta", "=", "0"],
                    ["field_data_field_product.delta", "=", "0"],
                    ["file_managed.status", "=", "1"],
                    // ["node.nid" , "=" , "954"],
                ])->groupBy("node.nid")
                ->select("node.nid", "node.type", "commerce_product.product_id", "commerce_product.sku", "node.title", DB::raw("field_data_field_long_title.field_long_title_value AS long_title"), "url_alias.alias", DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount"), 'field_data_field_mini_description.field_mini_description_value', "file_managed.fid", "file_managed.filename", "file_managed.uri", "field_data_field_images.field_images_alt", "field_data_field_images.field_images_title", "field_data_field_sell_price.field_sell_price_amount")
                ->orderBy("node.nid", "asc")
                ->limit($limit)
                ->offset($offset)
                ->get();
                
            if ($Nodes->first()) {
                $data = $temp = [];
                foreach ($Nodes as $node) {
                    $temp[] = [
                        "node_id" => $node->nid,
                        "product_type" => $node->type,
                        "product_id" => $node->product_id,
                        "sku" => $node->sku,
                        "title" => $node->title,
                        "long_title" => $node->long_title,
                        "url" => $node->alias,
                        "amount" => (int) $node->amount,
                        "sell_price_amount" => (!empty($node->field_sell_price_amount)) ? $node->field_sell_price_amount / 100 : 0,
                        "mini_desc" => $node->field_mini_description_value,
                        "image_url" => Config('constant.BAKINGO_IMAGE_BASE_URL'). str_replace("public:", "", str_replace("/", "", $node->uri))                        
                    ];
                }
                $data = $temp;
                $pagination = [
                    'current' => $currentPage,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'item_per_page' => $limit
                ];
                $response['pagination'] = $pagination;
                $messages = "Records Found Successfully";
                $response =  $this->ResponseComponent->success($messages, $data, $pagination);
            } else {
                // no record Found
                $messages = "No record Found";
                $response =  $this->ResponseComponent->error($messages);
            }
        } catch (Exception $e) { 
            $response =  $this->ResponseComponent->exception($e->getMessage());            
        }
        
        return response()->json($response);
        
        // return view('welcome' , $response);
    }


    /*
    

SELECT n.nid, n.type, n.title, n.status, cp.product_id, cp.sku, flt.field_long_title_value AS long_title, (cprice.commerce_price_amount / 100) AS amount, fmd.field_mini_description_value, (sprice.field_sell_price_amount / 100) AS sell_price, (costprice.field_cost_price_amount / 100) AS cost_price, descr.field_description_value as description, tdw.name AS weight, fweight.field_weight_tid, 
flav.field_flavour_tid, txf.name as flavour
FROM `node` as n 
INNER JOIN field_data_field_product fdfp ON fdfp.entity_id = n.nid
INNER JOIN commerce_product cp ON fdfp.field_product_product_id = cp.product_id
INNER JOIN field_data_field_long_title flt ON n.nid = flt.entity_id
INNER JOIN field_data_field_mini_description fmd ON n.nid = fmd.entity_id
LEFT JOIN field_data_field_sell_price sprice ON sprice.entity_id = cp.product_id
LEFT JOIN field_data_field_cost_price costprice ON costprice.entity_id = cp.product_id
LEFT JOIN field_data_commerce_price cprice ON cprice.entity_id = cp.product_id
LEFT JOIN field_data_field_description descr ON descr.entity_id = n.nid
LEFT JOIN field_data_field_weight fweight ON fweight.entity_id = cp.product_id
LEFT JOIN taxonomy_term_data tdw ON tdw.tid = fweight.field_weight_tid
LEFT JOIN field_data_field_flavour flav ON flav.entity_id = n.nid
LEFT JOIN taxonomy_term_data txf ON txf.tid = flav.field_flavour_tid
WHERE n.type IN ('regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon') AND (n.status = 1) AND (n.nid = 4672)
    
    */
   

    /*
    * function : getdetails to get the product details
    * params : nid
    * result : json/array
    *  */

    public function getdetails($nid){
      try{
        $Nodes = Nodes::join('field_data_field_product' , function($join){
            $join->on('field_data_field_product.entity_id', '=', 'node.nid'); 
        })->join('commerce_product' , function($join){
            $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
        })->join('field_data_field_long_title' , function($join){
            $join->on('field_data_field_long_title.entity_id', '=', 'node.nid');
        })->join('field_data_field_mini_description' , function($join){
            $join->on('field_data_field_mini_description.entity_id', '=', 'node.nid');
        })->join('field_data_field_description' , function($join){
            $join->on('field_data_field_description.entity_id', '=', 'node.nid');
        })->leftjoin('field_data_commerce_price' , function($join){
            $join->on('field_data_commerce_price.entity_id', '=', 'commerce_product.product_id');
        })->leftjoin('field_data_field_sell_price' , function($join){
            $join->on('field_data_field_sell_price.entity_id', '=', 'commerce_product.product_id');
        // })->leftjoin('field_data_field_cost_price' , function($join){
        //     $join->on('field_data_field_cost_price.entity_id', '=', 'commerce_product.product_id');
        })->leftjoin('field_data_field_weight' , function($join){
            $join->on('field_data_field_weight.entity_id', '=', 'commerce_product.product_id');
        })->leftjoin('taxonomy_term_data' , function($join){
            $join->on('taxonomy_term_data.tid', '=', 'field_data_field_weight.field_weight_tid');
        })->leftjoin('field_data_field_flavour' , function($join){
            $join->on('field_data_field_flavour.entity_id', '=', 'node.nid');
        })->leftjoin('taxonomy_term_data as txflv' , function($join){
            $join->on('txflv.tid', '=', 'field_data_field_flavour.field_flavour_tid');
        // })->leftjoin('field_data_field_tx_occasion_list' , function($join){
        //     $join->on('field_data_field_tx_occasion_list.entity_id', '=', 'node.nid');
        // })->leftjoin('taxonomy_term_data as txocc' , function($join){
        //     $join->on('txocc.tid', '=', 'field_data_field_tx_occasion_list.field_tx_occasion_list_tid');
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"],
            ["node.nid" , "=" , $nid]
            ])
        // ->groupBy("commerce_product.product_id")
        ->select("node.nid", "node.type", "commerce_product.product_id", "commerce_product.sku", "node.title",
          DB::raw("field_data_field_long_title.field_long_title_value AS long_title"),
          DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount") ,
          DB::raw("(field_data_field_sell_price.field_sell_price_amount / 100) AS sell_price") ,
          DB::raw("(taxonomy_term_data.name) AS weight"), "field_data_field_weight.field_weight_tid",
          DB::raw("(txflv.name) AS flavour"), "field_data_field_flavour.field_flavour_tid",
        //   DB::raw("(txocc.name) AS occasion"), "field_data_field_tx_occasion_list.field_tx_occasion_list_tid",
           'field_data_field_mini_description.field_mini_description_value')
        ->get();
        // ->toSql();
        // print_r($Nodes);
        // exit;

        // get images
        $NodesImg = Nodes::join('field_data_field_images' , function($join){
            $join->on('field_data_field_images.entity_id', '=', 'node.nid'); 
        })->join('file_managed' , function($join){
            $join->on('file_managed.fid', '=', 'field_data_field_images.field_images_fid');
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"],
            ["node.nid" , "=" , $nid]
            ])
        // ->groupBy("commerce_product.product_id")
        ->select("file_managed.fid", "file_managed.filename", "file_managed.uri", "field_data_field_images.field_images_alt", "field_data_field_images.field_images_title" )
        ->orderBy("field_data_field_images.delta")
        ->get();
        // dd($NodesImg);

        $response = [];
        if($Nodes->first()){
            $response = [
                "node_id" => $Nodes[0]->nid,
                "product_type" => $Nodes[0]->type,
                "product_id" => $Nodes[0]->product_id,
                "sku" => $Nodes[0]->sku,
                "title" => $Nodes[0]->title,
                "long_title" => $Nodes[0]->long_title,
                "amount" => $Nodes[0]->amount,
                "sell_price" => $Nodes[0]->sell_price,
                "mini_desc" => $Nodes[0]->field_mini_description_value,                
            ];
            $wt = $fl = $oc = [];
            foreach($Nodes as $node){
                // prepare weight array here
                $wt[$node->field_weight_tid] = [
                    "weight" => $node->weight,
                    "price" => $node->amount,
                    "sprice" => $node->sell_price,
                    // "cprice" => $node->amount,
                ];
                // prepare flavour array here
                if($node->field_flavour_tid != null) {
                  $fl[$node->field_flavour_tid] = $node->flavour;
                }
                // prepare occasion array here
                if($node->field_tx_occasion_list_tid != null) {
                  $oc[$node->field_tx_occasion_list_tid] = $node->occasion;
                }
            }
            $response['attributes']['weight'] = $wt;
            $response['attributes']['flavour'] = $fl;
            $response['attributes']['occasion'] = $oc;
            // $response['product']['attributes']['occasion'] = $fl;
        } else {
            // no record Found
            $messages = "No record Found";
            $response =  $this->ResponseComponent->error($messages);
        }
        $images = [];
        if($NodesImg->first()) {
          foreach($NodesImg as $node) {
            $images[] = [
              "fid" => $node->fid,
              "name" => $node->filename,
              "uri" => Config('constant.BAKINGO_IMAGE_BASE_URL'). str_replace("public:", "", str_replace("/", "", $node->uri)),
            ];
          }
        }
        $response['images'] = $images;
        $messages = "Records Found Successfully";
        $response =  $this->ResponseComponent->success($messages, $response);
      } catch (Exception $e) { 
        $response =  $this->ResponseComponent->exception($e->getMessage());            
      }

      return response()->json($response);
    }
}
