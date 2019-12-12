<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Bakingo\Nodes;
use App\Models\Api_products;
use App\Models\Api_product_images;
use App\Models\Api_product_price;
use App\Models\Api_attribute_map;

class SyncData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This script is to sync database tables of new structure for APIs';

    // attribute list of product it must be machine name in taxonomy_vocabulary
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

    protected $cron_interval = 900; // cron interval time in seconds

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
    }

    /**
     * Execute the console command.
     * This is for syncing data from node/product. Syncs data of catelog
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $response = [
            "message" => "No Record Found"
        ];
        Log::channel('datasync')->info("SUCCESS sync run @ : " . date('Y-m-d H:i:s') );

        // code for new data insertion here
        $insertedNodes = DB::connection('bakingo_mysql')->table("node")
        ->leftjoin('api_products as ap' , function($join){
            $join->on('ap.nid', '=', 'node.nid');
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"],
            ["ap.nid" , "=" , null]
            ])
        ->groupBy("node.nid")
        ->select("node.nid")
        ->get();

        $errorNodeAttributeIN = $successNodeAttributeIN = [];
        $errorNodeAttributeUP = $successNodeAttributeUP = [];
        $errorNodeAttributeDL = $successNodeAttributeDL = [];
        $hasError = ['IN' => false, 'UP'=> false, 'DL'=>false];

        if($insertedNodes->first()){
            
            foreach($insertedNodes as $key => $node) {

              // update into products table
              $productResult = $this->doEntryInProducts($node->nid);
              if($productResult===false) {
                $errorNodeAttributeIN["product"][] = $node->nid;
                $hasError['IN'] = true;
              } else {
                $successNodeAttributeIN["product"][] = $node->nid;
              }

              // update into product images table
              $productImgResult = $this->doEntryInProductImages($node->nid);
              if($productImgResult===false) {
                $hasError['IN'] = true;
                $errorNodeAttributeIN["img"][] = $node->nid;
              } else {
                $successNodeAttributeIN["img"][] = $node->nid;
              }

              // insert into product price table
              $productPriceResult = $this->doEntryInProductPrice($node->nid);
              if($productPriceResult===false) {
                $hasError['IN'] = true;
                $errorNodeAttributeIN["price"][] = $node->nid;
              } else {
                $successNodeAttributeIN["price"][] = $node->nid;
              }

              // insert into product attribute mapping table
              $productAttrResult = $this->doEntryProductAttribute($node->nid);
              if($productAttrResult===false) {
                $hasError['IN'] = true;
                $errorNodeAttributeIN["attr"][] = $node->nid;
              } else {
                $successNodeAttributeIN["attr"][] = $node->nid;
              }

            }
            // log error node of attribute
        }


        // start : code for updation data what changes made in NODE table
        $updatedNodes = DB::connection('bakingo_mysql')->table("node")
        ->leftjoin('api_products as ap' , function($join){
            $join->on('ap.nid', '=', 'node.nid');
        })->whereIn("node.type" , ['regular_cake' , 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
        ->where([
            ["node.status" , "=" , "1"],
            ["node.changed" , "<>" , DB::raw("ap.updated")]
            ])
        ->orderBy("node.nid")
        ->select("node.nid")
        ->get();


        if($updatedNodes->first()){
            
            foreach($updatedNodes as $key => $node) {

              // update into products table
              $productResult = $this->updateInProducts($node->nid);
              if($productResult===false) {
                $hasError['UP'] = true;
                $errorNodeAttributeUP["product"][] = $node->nid;
              } else {
                $successNodeAttributeUP["product"][] = $node->nid;
              }

              // update into product images table
              $productImgResult = $this->updateInProductImages($node->nid);
              if($productImgResult===false) {
                $hasError['UP'] = true;
                $errorNodeAttributeUP["img"][] = $node->nid;
              } else {
                $successNodeAttributeUP["img"][] = $node->nid;
              }

              // insert into product price table
              $productPriceResult = $this->updateInProductPrice($node->nid);
              if($productPriceResult===false) {
                $hasError['UP'] = true;
                $errorNodeAttributeUP["price"][] = $node->nid;
              } else {
                $successNodeAttributeUP["price"][] = $node->nid;
              }

              // insert into product attribute mapping table
              $productAttrResult = $this->updateProductAttribute($node->nid);
              if($productAttrResult===false) {
                $hasError['UP'] = true;
                $errorNodeAttributeUP["attr"][] = $node->nid;
              } else {
                $successNodeAttributeUP["attr"][] = $node->nid;
              }

            }
            // log error node of attribute
        }
        // end : code for updation data what changes made in NODE table


        // start : code for deleting node from api table

        $deletedNodes = DB::connection('bakingo_mysql')->table("node")
        ->rightjoin('api_products as ap' , function($join){
            $join->on('ap.nid', '=', 'node.nid');
        })->where([
            ["node.nid" , "=" , null]
            ])
        ->select("ap.nid")
        ->get();

        if($deletedNodes->first()){
            foreach($deletedNodes as $key => $node) {
              // delete from products table
              $deleteResult = $this->deleteFromTable($node->nid);
              if($deleteResult===false) {
                $hasError['DL'] = true;
                $errorNodeAttributeDL["attr"][] = $node->nid;
              } else {
                $successNodeAttributeDL["attr"][] = $node->nid;
              }
            }
        }

        // log the error here
        if($hasError['IN'] || $hasError['UP'] || $hasError['DL']) {
            $response["success"] = false;
            $response["message"] = "Error, While syncing data";
            Log::channel('datasync')->info("FAIL nodes : " . json_encode($errorNodeAttributeIN) );
            Log::channel('datasync')->info("FAIL nodes : " . json_encode($errorNodeAttributeUP) );
            Log::channel('datasync')->info("FAIL nodes : " . json_encode($errorNodeAttributeDL) );
        } else {
            $response["success"] = true;
            $response["message"] = "Successfully, data sync done";
            // Log::channel('datasync')->info("SUCCESS");
        }
        echo json_encode($response);
    }

    protected function doEntryInProducts($nid){
        DB::beginTransaction();
        $flag = true;
        try{
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
                ["node.nid" , "=" , $nid]
                ])
            ->groupBy("node.nid")
            ->select("node.nid", "node.type", "node.status", "node.title", "node.created","node.changed", "url_alias.alias",
            DB::raw("field_data_field_long_title.field_long_title_value AS long_title"),
            DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount") ,
            DB::raw("(field_data_field_sell_price.field_sell_price_amount / 100) AS sell_price") ,
            'field_data_field_mini_description.field_mini_description_value',
            'field_data_field_description.field_description_value')
            ->get();

            if(!empty($Nodes)){
                foreach($Nodes as $val) {
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


    protected function updateInProducts($nid){
        DB::beginTransaction();
        $flag = true;
        try{
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
                ["node.nid" , "=" , $nid]
                ])
            ->groupBy("node.nid")
            ->select("node.nid", "node.type", "node.status", "node.title", "node.created","node.changed", "url_alias.alias",
            DB::raw("field_data_field_long_title.field_long_title_value AS long_title"),
            DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount") ,
            DB::raw("(field_data_field_sell_price.field_sell_price_amount / 100) AS sell_price") ,
            'field_data_field_mini_description.field_mini_description_value',
            'field_data_field_description.field_description_value')
            ->get();

            if(!empty($Nodes)){
                foreach($Nodes as $val) {
                    $Api_products = Api_products::where([
                        ["merchant_id", "=", $this->merchant_id],
                        ["nid", "=", $nid]
                    ])->first();

                    // insert in product image table here
                    if(!empty($Api_products)) {
                      $Api_products->merchant_id = $this->merchant_id;
                      $Api_products->nid = $val->nid;
                      $Api_products->type = $val->type;
                      $Api_products->status = $val->status;
                      $Api_products->title = $val->title;
                      $Api_products->alias = $val->alias;
                      $Api_products->long_title = $val->long_title;
                      $Api_products->amount = $val->amount;
                      $Api_products->sell_price = $val->sell_price;
                      $Api_products->mini_descr = $val->field_mini_description_value;
                      $Api_products->descr = $val->field_description_value;
                      $Api_products->updated = $val->changed;
                      $Api_products->save(); // save data
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

    protected function updateInProductImages($nid){
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
                     
                    $Api_product_images = Api_product_images::where([
                        ["merchant_id", "=", $this->merchant_id],
                        ["nid", "=", $nid],
                        ["fid", "=", $val->fid]
                    ])->first();

                    if(!empty($Api_product_images)) {
                      // update the row
                      $Api_product_images->merchant_id = $this->merchant_id;
                      $Api_product_images->nid = $val->nid;
                      $Api_product_images->fid = $val->fid;
                      $Api_product_images->filename = $val->filename;
                      $Api_product_images->uri = $val->uri;
                      $Api_product_images->field_images_alt = $val->field_images_alt;
                      $Api_product_images->field_images_title = $val->field_images_title;
                      $Api_product_images->sort_order = $val->delta;
                    } else {
                      // insert the row
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
                      $Api_product_images = new Api_product_images($insertData);
                    }
                    $Api_product_images->save();
                }
            }
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }

    protected function updateInProductPrice($nid){
        DB::beginTransaction();
        $flag = true;
        $nidExistsInPriceTable = [];
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
                    $Api_product_price = Api_product_price::where([
                        ["merchant_id", "=", $this->merchant_id],
                        ["nid", "=", $nid],
                        ["pid", "=", $val->product_id]
                    ])->first();
                    $nidExistsInPriceTable[] = $val->product_id; // prepare the array of exist pid

                    // insert in product image table here
                    if(!empty($Api_product_price)) {
                        // update the row
                        $Api_product_price->merchant_id = $this->merchant_id;
                        $Api_product_price->nid = $val->nid;
                        $Api_product_price->sku = $val->sku;
                        $Api_product_price->pid = $val->product_id;
                        $Api_product_price->weight = $val->weight;
                        $Api_product_price->price = $val->amount;
                        $Api_product_price->sprice = $val->sprice;
                        $Api_product_price->cprice = $val->cprice;
                    } else {
                        // insert the row
                        $insertData = [
                          'merchant_id' => $this->merchant_id,
                          'nid' => $val->nid,
                          'sku' => $val->sku,
                          'pid' => $val->product_id,
                          'weight' => $val->weight,
                          'price' => $val->amount,
                          'sprice' => $val->sprice,
                          'cprice' => $val->cprice
                          ];
                        $Api_product_price = new Api_product_price($insertData);
                      }
                      $Api_product_price->save();
                }
                // delete the data from price table if product varient is deleted
                $priceResult = DB::connection('bakingo_mysql')->table("api_product_price as n")
                ->where([
                    ['n.nid', '=', $nid] ,
                ])
                ->get();

                if(!empty($priceResult)) {
                  foreach($priceResult as $val) {
                    if(!in_array($val->pid, $nidExistsInPriceTable)) {
                        // delete
                        Api_product_price::where([
                            ["merchant_id", "=", $this->merchant_id],
                            ["nid", "=", $nid],
                            ["pid", "=", $val->pid]
                        ])->delete();
                    }
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


    /*
    * function : updateProductAttribute to update the product attribute
    * params : nid
    * result : json/array
    **/
    protected function updateProductAttribute($nid){
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
                        // delete the attribute first
                        $affectedRowsDelete = Api_attribute_map::where([
                            ['merchant_id' , '=' , $this->merchant_id ],
                            ['nid' , '=' , $flav->nid],
                            ['attr_id' , '=' , $flav->{"field_{$attr}_tid"}],
                            ['attr_type' , '=' , $flav->{"{$attr}_vid"}],
                        ])->delete();
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

    protected function deleteFromTable($nid){
        DB::beginTransaction();
        $flag = true;
        try{
            Api_products::where([
            ['merchant_id' , '=' , $this->merchant_id ],
            ['nid' , '=' , $nid],
            ])->delete();              
            Api_product_images::where([
            ['merchant_id' , '=' , $this->merchant_id ],
            ['nid' , '=' , $nid],
            ])->delete();
            Api_product_price::where([
            ['merchant_id' , '=' , $this->merchant_id ],
            ['nid' , '=' , $nid],
            ])->delete();
            Api_attribute_map::where([
            ['merchant_id' , '=' , $this->merchant_id ],
            ['nid' , '=' , $nid],
            ])->delete();

        } catch(Exception $e){
            DB::rollback();
            $flag = false;
        }
        return $flag;
    }


}
