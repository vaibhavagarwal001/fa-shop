<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Bakingo\Nodes;
use App\Models\Bakingo\Api_products;
use App\Models\Bakingo\Api_product_images;
use App\Models\Bakingo\Api_product_price;
use App\Models\Bakingo\Api_attribute_map;

class MigrateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This script is to migrate data from old database';

    protected $attributeList = [
        "flavour",
        "city",
        "events",
        "attributes",
        "age_group",
        "relationship",
        "tx_gender", // can not use gender it has not tid column direct value in feild data feild table
        "tx_occasion_list",
        "age_milestone",
    ];

    protected $merchant_id = 2; // merchant id 2 for bakingo

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $response = [
            "message" => "No Record Found"
        ];
        Log::channel('migration')->info("SUCCESS migration run @ : " . date('Y-m-d H:i:s') );
        $Nodes = DB::connection('bakingo_mysql')->table("node")
        ->join('field_data_field_product' , function($join){
            $join->on('field_data_field_product.entity_id', '=', 'node.nid'); 
        })->join('commerce_product' , function($join){
            $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
        })->leftjoin('field_data_field_long_title' , function($join){
            $join->on('field_data_field_long_title.entity_id', '=', 'node.nid');
        })->leftjoin('field_data_field_mini_description' , function($join){
            $join->on('field_data_field_mini_description.entity_id', '=', 'node.nid');
        })->leftjoin('field_data_field_description' , function($join){
            $join->on('field_data_field_description.entity_id', '=', 'node.nid');
        })->leftjoin('field_data_commerce_price' , function($join){
            $join->on('field_data_commerce_price.entity_id', '=', 'commerce_product.product_id');
        })->leftjoin('field_data_field_sell_price' , function($join){
            $join->on('field_data_field_sell_price.entity_id', '=', 'commerce_product.product_id');
        })->leftjoin('url_alias' , function($join){
            $join->on('url_alias.source',  '=', DB::raw("CONCAT('node/', node.nid)"));
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"],
            ])
        ->groupBy("node.nid")
        ->select("node.nid", "node.type", "node.status", "node.title", "node.created","node.changed", "url_alias.alias",
          DB::raw("field_data_field_long_title.field_long_title_value AS long_title"),
          DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount") ,
          DB::raw("(field_data_field_sell_price.field_sell_price_amount / 100) AS sell_price") ,
           'field_data_field_mini_description.field_mini_description_value',
           'field_data_field_description.field_description_value')
           ->get();

        $errorNodeAttribute = $successNodeAttribute = [];
        if($Nodes->first()){
            // insert into products table
            $productResult = $this->doEntryInProducts($Nodes);
            // if($productResult===false) {
            // $errorNodeAttribute["product"][] = $node->nid;
            // } else {
            // $successNodeAttribute["product"][] = $node->nid;
            // }

            // do entry in all tables
            foreach($Nodes as $key => $node) {

              // insert into product images table
              $productImgResult = $this->doEntryInProductImages($node->nid);
              if($productImgResult===false) {
                $errorNodeAttribute["img"][] = $node->nid;
              } else {
                $successNodeAttribute["img"][] = $node->nid;
              }

              // insert into product price table
              $productPriceResult = $this->doEntryInProductPrice($node->nid);
              if($productPriceResult===false) {
                $errorNodeAttribute["price"][] = $node->nid;
              } else {
                $successNodeAttribute["price"][] = $node->nid;
              }

              // insert into product attribute mapping table
              $productAttrResult = $this->doEntryProductAttribute($node->nid);
              if($productAttrResult===false) {
                $errorNodeAttribute["attr"][] = $node->nid;
              } else {
                $successNodeAttribute["attr"][] = $node->nid;
              }
              
            }
            // log error node of attribute

            if(count($errorNodeAttribute)) {
                $response["success"] = false;
                $response["message"] = "Error, While updating node or product attribute";
                Log::channel('migration')->info("FAIL node : " . json_encode($errorNodeAttribute) );
            } else {
                $response["success"] = true;
                $response["message"] = "Successfully, data inserted";
            }
        }
        echo json_encode($response);
    }

    /*
    * function : updateProductAttribute to update the product attribute
    * params : nid
    * result : json/array
    **/
    protected function doEntryInProducts($Nodes){
        DB::beginTransaction();
        $flag = true;
        try{
            if(!empty($Nodes)){
                foreach($Nodes as $val) {
                    // echo $val->changed;
                    // insert in product image table here
                    if(($val->nid != null)) {
                      $insertData = [
                        'merchant_id' => $this->merchant_id,
                        'nid' => $val->nid,
                        'type' => $val->type,
                        'status' => $val->status,
                        'title' => $val->title,
                        'alias' => $val->alias,
                        'long_title' => $val->long_title,
                        'amount' => $val->amount,
                        'sell_price' => $val->sell_price,
                        'cost_price' => 0,
                        'product_category' => '',
                        'mini_descr' => $val->field_mini_description_value,
                        'descr' => $val->field_description_value,
                        'updated' => $val->changed,
                        'created' => $val->created,
                      ];
                      $attrMapping = new Api_products($insertData);
                      $attrMapping->save();
                    }
                }
            }
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }

    protected function doEntryInProductImages($nid){
        DB::beginTransaction();
        $flag = true;
        try{
            $attrResult = DB::connection('bakingo_mysql')->table("field_data_field_images as a")
            ->join("file_managed as b", function ($join) {
                $join->on("b.fid", '=', 'a.field_images_fid');
            })
            ->join('node as n', function ($join) {
                $join->on('n.nid', '=', "a.entity_id");
            })
            ->select( "n.nid", "b.fid", "b.filename", "b.uri", "a.field_images_alt", "a.field_images_title", "a.delta" )
            ->where([
                ['n.nid', '=', $nid] ,
            ])->get();

            if(!empty($attrResult)){
                foreach($attrResult as $val) {
                     
                    // insert in product image table here
                    if(($val->nid != null)) {
                      $insertData = [
                        'merchant_id' => $this->merchant_id,
                        'nid' => $val->nid,
                        'fid' => $val->fid,
                        'filename' => $val->filename,
                        'uri' => $val->uri,
                        'field_images_alt' => $val->field_images_alt,
                        'field_images_title' => $val->field_images_title,
                        'sort_order' => $val->delta
                      ];
                      $attrMapping = new Api_product_images($insertData);
                      $attrMapping->save();
                    }
                }
            }
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }

    protected function doEntryInProductPrice($nid){
        DB::beginTransaction();
        $flag = true;
        try{
            $attrResult = DB::connection('bakingo_mysql')->table("node as n")
            ->join("field_data_field_product as b", function ($join) {
                $join->on("b.entity_id", '=', 'n.nid');
            })
            ->join('commerce_product as cp', function ($join) {
                $join->on('b.field_product_product_id', '=', "cp.product_id");
            })
            ->leftjoin('field_data_commerce_price as price', function ($join) {
                $join->on('price.entity_id', '=', "cp.product_id");
            })
            ->leftjoin('field_data_field_sell_price as sprice', function ($join) {
                $join->on('sprice.entity_id', '=', "cp.product_id");
            })
            ->leftjoin('field_data_field_cost_price as costprice', function ($join) {
                $join->on('costprice.entity_id', '=', "cp.product_id");
            })
            ->leftjoin('field_data_field_weight as fweight', function ($join) {
                $join->on('fweight.entity_id', '=', "cp.product_id");
            })
            ->leftjoin('taxonomy_term_data as tdw', function ($join) {
                $join->on('tdw.tid', '=', "fweight.field_weight_tid");
            })
            ->select( "n.nid", "cp.sku", "cp.product_id",
            DB::raw("(tdw.name) AS weight"),
            DB::raw("(price.commerce_price_amount / 100) AS amount"),
            DB::raw("(sprice.field_sell_price_amount / 100) AS sprice"),
            DB::raw("(costprice.field_cost_price_amount / 100) AS cprice")            
            )
            ->where([
                ['n.nid', '=', $nid] ,
            ])->get();

            if(!empty($attrResult)){
                foreach($attrResult as $val) {
                     
                    // insert in product image table here
                    if(($val->nid != null)) {
                      $insertData = [
                        'merchant_id' => $this->merchant_id,
                        'nid' => $val->nid,
                        'pid' => $val->product_id,
                        'price' => $val->amount,
                        'sprice' => !empty($val->sprice) ? $val->sprice : 0,
                        'cprice' => !empty($val->cprice) ? $val->cprice : 0,
                        'weight' => $val->weight,
                        'sku' => $val->sku
                      ];
                      $attrMapping = new Api_product_price($insertData);
                      $attrMapping->save();
                    }
                }
            }
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }

    protected function doEntryProductAttribute($nid){
        DB::beginTransaction();
        $flag = true;
        try{
            foreach($this->attributeList as $attr) {

                $attrResult = Nodes::leftjoin("field_data_field_{$attr}", function ($join) use($attr) {
                    $join->on("field_data_field_{$attr}.entity_id", '=', 'node.nid');
                })
                ->leftjoin('taxonomy_term_data', function ($join) use($attr) {
                    $join->on('taxonomy_term_data.tid', '=', "field_data_field_{$attr}.field_{$attr}_tid");
                })
                ->leftjoin('taxonomy_vocabulary', function ($join) use($attr) {
                    $join->on('taxonomy_vocabulary.vid', '=', "taxonomy_term_data.vid");
                })
                ->select("node.nid", "field_data_field_{$attr}.field_{$attr}_tid",
                 DB::raw("(taxonomy_term_data.vid) AS {$attr}_vid") )
                ->where([
                    ['node.nid', '=', $nid] ,
                ])->get();
    
                if(!empty($attrResult)){
                    foreach($attrResult as $flav) {
                        // insert attribute here
                        if(($flav["field_{$attr}_tid"] != null) && ($flav["{$attr}_vid"] != null)) {
                          $insertData = [
                            'merchant_id' => $this->merchant_id,
                            'nid' => $flav["nid"],
                            'attr_id' => $flav["field_{$attr}_tid"],
                            'attr_type' => $flav["{$attr}_vid"]
                          ];
                          $attrMapping = new Api_attribute_map($insertData);
                          $attrMapping->save();
                        }
                    }
                }
                DB::commit();
            }

        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }
    

}
