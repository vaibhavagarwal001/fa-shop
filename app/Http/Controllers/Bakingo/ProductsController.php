<?php

namespace App\Http\Controllers\Bakingo;

use Illuminate\Http\Request;

// controllers
use App\Http\Controllers\Component\ResponseComponent;
use App\Http\Controllers\Component\ValidateComponent;
use App\Http\Controllers\Bakingo\MetaInfoController;

/** Models */

use App\Models\Bakingo\Nodes;
use App\Models\Bakingo\PlViewUrlMapping as BakingoUrls;
use App\Models\Bakingo\Api_products;
use App\Models\Bakingo\Api_product_price;
use App\Models\Bakingo\Api_product_images;
use App\Models\Bakingo\Api_attribute_map;
use App\Models\Bakingo\MenuRouters;

/** Models */

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Models\Bakingo\TaxonomyVocabulary;
use App\Models\Bakingo\ViewsDisplay;
use App\Models\Bakingo\ViewsView;
use Exception;
use Illuminate\Support\Facades\Config;

class ProductsController extends Controller
{

    protected $ResponseComponent;
    protected $MetaInfoController;
    protected $helper;

    protected $tbl_api_products;
    protected $tbl_api_product_images;
    protected $tbl_api_product_price;
    protected $tbl_api_attribute_map;

    protected $tbl_taxonomy_term_data;
    protected $tbl_taxonomy_vocabulary;

    protected $merchant_id; // merchant id 2 for bakingo

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


    public function __construct()
    {
        $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
        $this->ResponseComponent = new ResponseComponent();

        $this->helper = new ValidateComponent();
        $this->MetaInfoController = new MetaInfoController();
        
        // define table names
        $this->tbl_api_products = "api_products";
        $this->tbl_api_product_images = "api_product_images";
        $this->tbl_api_product_price = "api_product_price";
        $this->tbl_api_attribute_map = "api_attribute_map";

        $this->tbl_taxonomy_term_data = "taxonomy_term_data";
        $this->tbl_taxonomy_vocabulary = "taxonomy_vocabulary";
        $this->merchant_id = Config::get("constant.MERCHANT_ID.BAKINGO");
    }

    //
    /**
     * 
     * 
     * 
     */
    public function getListingCount($nodeIds , $requestData)
    {
        $Nodes = Api_Products::select("api_products.nid");
        $field_attributes_tid = isset($requestData['field_attributes_tid']) ? $requestData['field_attributes_tid'] : "";
        $field_age_group_tid = isset($requestData['field_age_group_tid']) ? $requestData['field_age_group_tid'] : "";
        $field_tx_gender_tid = isset($requestData['field_tx_gender_tid']) ? $requestData['field_tx_gender_tid'] : "";
        $field_occasion_value = isset($requestData['field_occasion_value']) ? $requestData['field_occasion_value'] : "";
        $field_flavour_tid = isset($requestData['field_flavour_tid']) ? $requestData['field_flavour_tid'] : "";
        
        if(!empty($field_attributes_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_attribute', function ($join) use ($field_attributes_tid) {
                $join->on('filter_attribute.nid', '=', 'api_products.nid');
                $join->whereIn('filter_attribute.attr_id' , $field_attributes_tid);
                $join->where('filter_attribute.merchant_id' , $this->merchant_id);
            });
        }

        if(!empty($field_age_group_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_age_group', function ($join) use ($field_age_group_tid) {
                $join->on('filter_age_group.nid', '=', 'api_products.nid');
                $join->whereIn('filter_age_group.attr_id' , $field_age_group_tid);
                $join->where('filter_age_group.merchant_id' , $this->merchant_id);
            });
        }

        if(!empty($field_tx_gender_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_gender', function ($join) use ($field_tx_gender_tid) {
                $join->on('filter_gender.nid', '=', 'api_products.nid');
                $join->whereIn('filter_gender.attr_id' , $field_tx_gender_tid);
                $join->where('filter_gender.merchant_id' , $this->merchant_id);
            });
        }

        if(!empty($field_occasion_value)){
            $Nodes = $Nodes->join('field_data_field_occasion as filter_occasion', function ($join) use ($field_occasion_value) {
                $join->on('filter_occasion.entity_id', '=', 'api_products.nid');
                $join->whereIn('filter_occasion.field_occasion_value' , $field_occasion_value);
                // $join->where('filter_occasion.merchant_id' , $this->merchant_id);
            });
        }

        if(!empty($field_flavour_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_flavour', function ($join) use ($field_flavour_tid) {
                $join->on('filter_flavour.nid', '=', 'api_products.nid');
                $join->whereIn('filter_flavour.attr_id' , $field_flavour_tid);
                $join->where('filter_flavour.merchant_id' , $this->merchant_id);
            });
        }

        $Nodes = $Nodes->whereIn("api_products.nid", $nodeIds)
            ->where([
                ["api_products.status", "=", "1"]
            ])->groupBy("api_products.nid")->get()->count();
        return $Nodes;
    }

    /**
     * 
     * 
     */
    public function getlisting(Request $request, $currentPage = 1)
    {
        try {
            $path = $request->path();
            $path = str_replace("api/bakingo/", "", $path);
            $nodeIds = ApiProducts::where([
                ["api_products.status", "=", "1"]
            ])->select('nid')->get();
            // $nodeIds = [1971 , 4982 ,188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209];

            // $UrlMapping = BakingoUrls::join('pl_view_product_mapping', function ($join) {
            //     $join->on('pl_view_product_mapping.pl_id', '=', 'pl_view_url_mapping.pl_id');
            // })->where([
            //     ["pl_view_url_mapping.view_display_url" ,"=" ,$path]
            // ])->select("nid")->get();

            // if($UrlMapping->first()){
            //     $nodeIds = [];
            //     foreach($UrlMapping as $UrlMappings){
            //         $nodeIds[] = $UrlMappings->nid;
            //     }
            // }
            $Nodes = ApiProducts::leftjoin('api_product_images', function ($join) {
                $join->on('api_product_images.nid', '=', 'api_products.nid');
                $join->where('api_product_images.sort_order' , 0);
            });

            $requestData = $request->all();
            $field_attributes_tid = isset($requestData['field_attributes_tid']) ? $requestData['field_attributes_tid'] : "";
            $field_age_group_tid = isset($requestData['field_age_group_tid']) ? $requestData['field_age_group_tid'] : "";
            $field_tx_gender_tid = isset($requestData['field_tx_gender_tid']) ? $requestData['field_tx_gender_tid'] : "";
            $field_occasion_value = isset($requestData['field_occasion_value']) ? $requestData['field_occasion_value'] : "";
            $field_flavour_tid = isset($requestData['field_flavour_tid']) ? $requestData['field_flavour_tid'] : "";
            
            $sort = isset($requestData['sort']) ? $requestData['sort'] : "field_product_order_value";
            $sort_order = isset($requestData['direction']) ? $requestData['direction'] : "ASC";
            $sortBy = "api_products.nid";

            if(!empty($sort)){
                switch($sort){
                    case "field_product_order_value":
                        $Nodes = $Nodes->leftjoin('field_data_field_product_order', function ($join) use ($sort) {
                            $join->on('field_data_field_product_order.entity_id', '=', 'api_products.nid');
                        });
                        $sortBy = "field_data_field_product_order.".$sort;
                        break;
                    case "commerce_price_amount":
                        $Nodes = $Nodes->leftjoin('api_product_price', function ($join) use ($sort) {
                            $join->on('api_product_price.nid', '=', 'api_products.nid');
                        });
                        $sortBy = "api_product_price.price";
                        break;
                }
            }
            
            if(!empty($field_attributes_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_attribute', function ($join) use ($field_attributes_tid) {
                    $join->on('filter_attribute.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_attribute.attr_id' , $field_attributes_tid);
                    $join->where('filter_attribute.merchant_id' , $this->merchant_id);
                });
            }

            if(!empty($field_age_group_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_age_group', function ($join) use ($field_age_group_tid) {
                    $join->on('filter_age_group.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_age_group.attr_id' , $field_age_group_tid);
                    $join->where('filter_age_group.merchant_id' , $this->merchant_id);
                });
            }

            if(!empty($field_tx_gender_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_gender', function ($join) use ($field_tx_gender_tid) {
                    $join->on('filter_gender.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_gender.attr_id' , $field_tx_gender_tid);
                    $join->where('filter_gender.merchant_id' , $this->merchant_id);
                });
            }

            if(!empty($field_occasion_value)){
                $Nodes = $Nodes->join('field_data_field_occasion as filter_occasion', function ($join) use ($field_occasion_value) {
                    $join->on('filter_occasion.entity_id', '=', 'api_products.nid');
                    $join->whereIn('filter_occasion.field_occasion_value' , $field_occasion_value);
                    // $join->where('filter_occasion.merchant_id' , $this->merchant_id);
                });
            }

            if(!empty($field_flavour_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_flavour', function ($join) use ($field_flavour_tid) {
                    $join->on('filter_flavour.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_flavour.attr_id' , $field_flavour_tid);
                    $join->where('filter_flavour.merchant_id' , $this->merchant_id);
                });
            }

            $totalRecords = $this->getListingCount($nodeIds , $requestData);
            $limit = 10;
            $offset = ($currentPage - 1) * $limit;
            $totalPages = ceil($totalRecords / $limit);

            
            $Nodes = $Nodes->whereIn("api_products.nid", $nodeIds)
                ->where([
                    ["api_products.status", "=", "1"]
                ])
                ->select("api_products.nid", "api_products.type", "api_products.long_title", "api_products.title", "api_products.alias", "api_products.amount", "api_products.amount", "api_products.sell_price", "api_products.descr", "api_product_images.uri")
                ->orderBy($sortBy, $sort_order)
                ->groupBy("api_products.nid")
                ->limit($limit)
                ->offset($offset)
                ->get();

            if ($Nodes->first()) {
                $data = $temp = [];
                foreach ($Nodes as $node) {
                    $temp[] = [
                        "node_id" => (int) $node->nid,
                        "product_type" => $node->type,
                        "title" => $node->title,
                        "long_title" => $node->long_title,
                        "url" => $node->alias,
                        "amount" => (int) $node->amount,
                        "sell_price_amount" =>  (int)  ((!empty($node->sell_price)) ? $node->sell_price : 0),
                        "mini_desc" => $node->mini_descr,
                        "image_url" => Config('constant.BAKINGO_IMAGE_BASE_URL') . str_replace("public:", "", str_replace("/", "", $node->uri))
                    ];
                }
                $data = $temp;
                $pagination = [
                    'current' => (int) $currentPage,
                    'total_pages' =>  (int) $totalPages,
                    'total_records' =>  (int) $totalRecords,
                    'item_per_page' => (int) $limit
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
    }

    /*
    * function : getdetails to get the product details
    * params : nid
    * result : json/array
    *  */
    public function getdetails($nid, Request $request)
    {
        try {
            $nid = $this->helper->valid($nid);
            $Nodes = Api_Products::join($this->tbl_api_product_price, function ($join) {
                $join->on($this->tbl_api_product_price .'.nid', '=', $this->tbl_api_products.'.nid');
            })
            ->join($this->tbl_api_product_images, function ($join) {
                $join->on($this->tbl_api_product_images .'.nid', '=', $this->tbl_api_products.'.nid');
            })
            ->where([
                ["{$this->tbl_api_products}.merchant_id", "=", $this->merchant_id],
                ["{$this->tbl_api_products}.status", "=", "1"],
                ["{$this->tbl_api_products}.nid", "=", $nid]
            ])
            // ->groupBy("commerce_product.product_id")
            ->select(
                "{$this->tbl_api_products}.nid",
                "{$this->tbl_api_products}.type",
                "{$this->tbl_api_products}.status",
                "{$this->tbl_api_products}.title",
                "{$this->tbl_api_products}.long_title",
                "{$this->tbl_api_products}.alias",
                "{$this->tbl_api_products}.amount",
                "{$this->tbl_api_products}.sell_price",
                "{$this->tbl_api_products}.mini_descr",
                "{$this->tbl_api_products}.descr",
                "{$this->tbl_api_product_price}.sku",
                "{$this->tbl_api_product_price}.weight",
                "{$this->tbl_api_product_price}.price",
                "{$this->tbl_api_product_price}.sprice",
                "{$this->tbl_api_product_price}.pid",
                "{$this->tbl_api_product_images}.fid",
                "{$this->tbl_api_product_images}.filename",
                "{$this->tbl_api_product_images}.sort_order",
                "{$this->tbl_api_product_images}.uri",
                "{$this->tbl_api_product_images}.field_images_alt",
                "{$this->tbl_api_product_images}.field_images_title"
            )
            ->get();

            $NodeAttributes = Api_products::leftjoin($this->tbl_api_attribute_map, function ($join) {
                $join->on($this->tbl_api_products .'.nid', '=', $this->tbl_api_attribute_map.'.nid');
            })
            ->leftjoin($this->tbl_taxonomy_vocabulary, function ($join) {
                $join->on($this->tbl_taxonomy_vocabulary .'.vid', '=', $this->tbl_api_attribute_map.'.attr_type');
            })
            ->leftjoin($this->tbl_taxonomy_term_data, function ($join) {
                $join->on($this->tbl_taxonomy_term_data .'.tid', '=', $this->tbl_api_attribute_map.'.attr_id');
            })
            ->where([
                ["{$this->tbl_api_products}.merchant_id", "=", $this->merchant_id],
                ["{$this->tbl_api_products}.nid", "=", $nid],
                // ["{$this->tbl_api_attribute_map}.nid", "=", $nid]
            ])
            ->groupBy("{$this->tbl_api_attribute_map}.attr_type")
            ->select(
                "{$this->tbl_api_products}.nid",
                "{$this->tbl_api_attribute_map}.attr_id",
                "{$this->tbl_api_attribute_map}.attr_type",
                "{$this->tbl_taxonomy_vocabulary}.machine_name",
                DB::raw("group_concat( DISTINCT ".$this->tbl_taxonomy_term_data.".name) as attribs")
            )->get();

            $response = [];
            $wt = $fl = $oc = $images = [];

            if ($Nodes->first()) {
                
                foreach ($Nodes as $key => $node) {
                    // print_r($node); die;
                    if($key == 0) {
                        $response = [
                            "node_id" => $node->nid,
                            "product_type" => $node->type,
                            // "product_id" => $node->pid,
                            // "sku" => $node->sku,
                            "title" => $node->title,
                            "long_title" => $node->long_title,
                            "amount" => $node->amount,
                            "sell_price" => $node->sell_price,
                            "mini_desc" => $node->mini_descr,
                            "description" => $node->descr,
                        ];
                    }
                    // prepare the images array
                    $images[$node->fid] = [
                        "sort_order" => $node->sort_order,
                        "fid" => $node->fid,
                        "name" => $node->filename,
                        "uri" => Config('constant.BAKINGO_IMAGE_BASE_URL') . str_replace("public:", "", str_replace("/", "", $node->uri)),
                    ];

                    // prepare weight array here
                    $wt[$node->pid] = [
                        "weight" => $node->weight,
                        "price" => (int)$node->price,
                        "sprice" => (int)$node->sell_price,
                        // "cprice" => $node->amount,
                    ];
                    
                }

                if($NodeAttributes->first()) {
                  foreach ($NodeAttributes as $node) {
                    // echo "<pre>";
                    // print_r($node);
                    // die;
                    $response['attributes'][$node->machine_name] = explode(",", $node->attribs);
                  }
                }

                $response['attributes']['weight'] = $wt;
                // $response['attributes']['flavour'] = $fl;
                // $response['attributes']['hasflavour'] = (count($fl) ? true : false);
                // $response['attributes']['occasion'] = $oc;
                // $response['attributes']['hasoccasion'] = (count($oc) ? true : false);
                // $response['product']['attributes']['occasion'] = $fl;

                // send meta information
                $params = $request->all();
                if(!empty($params) && ($params['metainfo'] == 1)) {
                  $metaInfo = $this->MetaInfoController->getMetaProductDetails($nid);
                  $metaData = $metaInfo->getData();
                  if(!empty($metaData->status_code) && ($metaData->status_code == 200)) {
                    $response['meta'] = !empty($metaData->data->meta) ? $metaData->data->meta : [];
                  }
                }

            } else {
                // no record Found
                $messages = "No record Found";
                $response =  $this->ResponseComponent->error($messages);
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
