<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bakingo\Nodes;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Component\ResponseComponent;
use Exception;
use Illuminate\Support\Facades\Config;

class ProductsController extends Controller
{
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
        $ResponseComponent = new ResponseComponent();
        try {
            $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
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
                        "image_url" => Config('constant.BAKINGO_IMAGE_BASE_URL'). str_replace("public:///", "sites/default/files/", $node->uri)
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
                $response =  $ResponseComponent->success($messages, $data, $pagination);
            } else {
                // no record Found
                $messages = "No record Found";
                $response =  $ResponseComponent->error($messages);
            }
        } catch (Exception $e) { 
            $response =  $ResponseComponent->exception($e->getMessage());            
        }
        
        return response()->json($response);
        
        // return view('welcome' , $response);
    }
}
