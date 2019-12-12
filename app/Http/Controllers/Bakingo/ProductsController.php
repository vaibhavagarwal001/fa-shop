<?php

namespace App\Http\Controllers\Bakingo;

use Illuminate\Http\Request;

/** Models */

use App\Models\Bakingo\Nodes;
use App\Models\Bakingo\PlViewUrlMapping as BakingoUrls;
use App\Models\Bakingo\ApiProducts;
use App\Models\Bakingo\MenuRouters;

/** Models */

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Component\ResponseComponent;
use App\Models\Bakingo\TaxonomyVocabulary;
use App\Models\Bakingo\ViewsDisplay;
use App\Models\Bakingo\ViewsView;
use Exception;
use Illuminate\Support\Facades\Config;

class ProductsController extends Controller
{


    protected $ResponseComponent;
    protected $merchantId;

    public function __construct()
    {
        $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
        $this->ResponseComponent = new ResponseComponent();
        $this->merchantId = Config::get("constant.MERCHANT_ID.BAKINGO");
    }

    //
    /**
     * 
     * 
     * 
     */
    public function getListingCount($nodeIds , $requestData)
    {
        $Nodes = ApiProducts::select("api_products.nid");
        $field_attributes_tid = isset($requestData['field_attributes_tid']) ? $requestData['field_attributes_tid'] : "";
        $field_age_group_tid = isset($requestData['field_age_group_tid']) ? $requestData['field_age_group_tid'] : "";
        $field_tx_gender_tid = isset($requestData['field_tx_gender_tid']) ? $requestData['field_tx_gender_tid'] : "";
        $field_occasion_value = isset($requestData['field_occasion_value']) ? $requestData['field_occasion_value'] : "";
        $field_flavour_tid = isset($requestData['field_flavour_tid']) ? $requestData['field_flavour_tid'] : "";
        
        if(!empty($field_attributes_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_attribute', function ($join) use ($field_attributes_tid) {
                $join->on('filter_attribute.nid', '=', 'api_products.nid');
                $join->whereIn('filter_attribute.attr_id' , $field_attributes_tid);
                $join->where('filter_attribute.merchant_id' , $this->merchantId);
            });
        }

        if(!empty($field_age_group_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_age_group', function ($join) use ($field_age_group_tid) {
                $join->on('filter_age_group.nid', '=', 'api_products.nid');
                $join->whereIn('filter_age_group.attr_id' , $field_age_group_tid);
                $join->where('filter_age_group.merchant_id' , $this->merchantId);
            });
        }

        if(!empty($field_tx_gender_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_gender', function ($join) use ($field_tx_gender_tid) {
                $join->on('filter_gender.nid', '=', 'api_products.nid');
                $join->whereIn('filter_gender.attr_id' , $field_tx_gender_tid);
                $join->where('filter_gender.merchant_id' , $this->merchantId);
            });
        }

        if(!empty($field_occasion_value)){
            $Nodes = $Nodes->join('field_data_field_occasion as filter_occasion', function ($join) use ($field_occasion_value) {
                $join->on('filter_occasion.entity_id', '=', 'api_products.nid');
                $join->whereIn('filter_occasion.field_occasion_value' , $field_occasion_value);
                // $join->where('filter_occasion.merchant_id' , $this->merchantId);
            });
        }

        if(!empty($field_flavour_tid)){
            $Nodes = $Nodes->join('api_attribute_map as filter_flavour', function ($join) use ($field_flavour_tid) {
                $join->on('filter_flavour.nid', '=', 'api_products.nid');
                $join->whereIn('filter_flavour.attr_id' , $field_flavour_tid);
                $join->where('filter_flavour.merchant_id' , $this->merchantId);
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
                    $join->where('filter_attribute.merchant_id' , $this->merchantId);
                });
            }

            if(!empty($field_age_group_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_age_group', function ($join) use ($field_age_group_tid) {
                    $join->on('filter_age_group.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_age_group.attr_id' , $field_age_group_tid);
                    $join->where('filter_age_group.merchant_id' , $this->merchantId);
                });
            }

            if(!empty($field_tx_gender_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_gender', function ($join) use ($field_tx_gender_tid) {
                    $join->on('filter_gender.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_gender.attr_id' , $field_tx_gender_tid);
                    $join->where('filter_gender.merchant_id' , $this->merchantId);
                });
            }

            if(!empty($field_occasion_value)){
                $Nodes = $Nodes->join('field_data_field_occasion as filter_occasion', function ($join) use ($field_occasion_value) {
                    $join->on('filter_occasion.entity_id', '=', 'api_products.nid');
                    $join->whereIn('filter_occasion.field_occasion_value' , $field_occasion_value);
                    // $join->where('filter_occasion.merchant_id' , $this->merchantId);
                });
            }

            if(!empty($field_flavour_tid)){
                $Nodes = $Nodes->join('api_attribute_map as filter_flavour', function ($join) use ($field_flavour_tid) {
                    $join->on('filter_flavour.nid', '=', 'api_products.nid');
                    $join->whereIn('filter_flavour.attr_id' , $field_flavour_tid);
                    $join->where('filter_flavour.merchant_id' , $this->merchantId);
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
    public function getdetails($nid)
    {
        try {
            $Nodes = Nodes::join('field_data_field_product', function ($join) {
                $join->on('field_data_field_product.entity_id', '=', 'node.nid');
            })->join('commerce_product', function ($join) {
                $join->on('field_data_field_product.field_product_product_id', '=', 'commerce_product.product_id');
            })->join('field_data_field_long_title', function ($join) {
                $join->on('field_data_field_long_title.entity_id', '=', 'node.nid');
            })->join('field_data_field_mini_description', function ($join) {
                $join->on('field_data_field_mini_description.entity_id', '=', 'node.nid');
            })->join('field_data_field_description', function ($join) {
                $join->on('field_data_field_description.entity_id', '=', 'node.nid');
            })->leftjoin('field_data_commerce_price', function ($join) {
                $join->on('field_data_commerce_price.entity_id', '=', 'commerce_product.product_id');
            })->leftjoin('field_data_field_sell_price', function ($join) {
                $join->on('field_data_field_sell_price.entity_id', '=', 'commerce_product.product_id');
                // })->leftjoin('field_data_field_cost_price' , function($join){
                //     $join->on('field_data_field_cost_price.entity_id', '=', 'commerce_product.product_id');
            })->leftjoin('field_data_field_weight', function ($join) {
                $join->on('field_data_field_weight.entity_id', '=', 'commerce_product.product_id');
            })->leftjoin('taxonomy_term_data', function ($join) {
                $join->on('taxonomy_term_data.tid', '=', 'field_data_field_weight.field_weight_tid');
            })->leftjoin('field_data_field_flavour', function ($join) {
                $join->on('field_data_field_flavour.entity_id', '=', 'node.nid');
            })->leftjoin('taxonomy_term_data as txflv', function ($join) {
                $join->on('txflv.tid', '=', 'field_data_field_flavour.field_flavour_tid');
                // })->leftjoin('field_data_field_tx_occasion_list' , function($join){
                //     $join->on('field_data_field_tx_occasion_list.entity_id', '=', 'node.nid');
                // })->leftjoin('taxonomy_term_data as txocc' , function($join){
                //     $join->on('txocc.tid', '=', 'field_data_field_tx_occasion_list.field_tx_occasion_list_tid');
            })->whereIn("node.type", ['regular_cake', 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
                ->where([
                    ["node.status", "=", "1"],
                    ["node.nid", "=", $nid]
                ])
                // ->groupBy("commerce_product.product_id")
                ->select(
                    "node.nid",
                    "node.type",
                    "commerce_product.product_id",
                    "commerce_product.sku",
                    "node.title",
                    DB::raw("field_data_field_long_title.field_long_title_value AS long_title"),
                    DB::raw("(field_data_commerce_price.commerce_price_amount / 100) AS amount"),
                    DB::raw("(field_data_field_sell_price.field_sell_price_amount / 100) AS sell_price"),
                    DB::raw("(taxonomy_term_data.name) AS weight"),
                    "field_data_field_weight.field_weight_tid",
                    DB::raw("(txflv.name) AS flavour"),
                    "field_data_field_flavour.field_flavour_tid",
                    //   DB::raw("(txocc.name) AS occasion"), "field_data_field_tx_occasion_list.field_tx_occasion_list_tid",
                    'field_data_field_mini_description.field_mini_description_value'
                )
                ->get();
            // ->toSql();
            // print_r($Nodes);
            // exit;

            // get images
            $NodesImg = Nodes::join('field_data_field_images', function ($join) {
                $join->on('field_data_field_images.entity_id', '=', 'node.nid');
            })->join('file_managed', function ($join) {
                $join->on('file_managed.fid', '=', 'field_data_field_images.field_images_fid');
            })->whereIn("node.type", ['regular_cake', 'cup_cake', 'jar_cakes', 'party_cake', 'pastries', 'photo_cake', 'plum_cake', 'theme_cake', 'addon'])
                ->where([
                    ["node.status", "=", "1"],
                    ["node.nid", "=", $nid]
                ])
                // ->groupBy("commerce_product.product_id")
                ->select("file_managed.fid", "file_managed.filename", "file_managed.uri", "field_data_field_images.field_images_alt", "field_data_field_images.field_images_title")
                ->orderBy("field_data_field_images.delta")
                ->get();
            // dd($NodesImg);

            $response = [];
            if ($Nodes->first()) {
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
                foreach ($Nodes as $node) {
                    // prepare weight array here
                    $wt[$node->field_weight_tid] = [
                        "weight" => $node->weight,
                        "price" => $node->amount,
                        "sprice" => $node->sell_price,
                        // "cprice" => $node->amount,
                    ];
                    // prepare flavour array here
                    if ($node->field_flavour_tid != null) {
                        $fl[$node->field_flavour_tid] = $node->flavour;
                    }
                    // prepare occasion array here
                    if ($node->field_tx_occasion_list_tid != null) {
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
            if ($NodesImg->first()) {
                foreach ($NodesImg as $node) {
                    $images[] = [
                        "fid" => $node->fid,
                        "name" => $node->filename,
                        "uri" => Config('constant.BAKINGO_IMAGE_BASE_URL') . str_replace("public:", "", str_replace("/", "", $node->uri)),
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


    public function getMetaInfo(Request $request)
    {
        try {
            $path = $request->path();
            $path = str_replace("api/bakingo/meta-info/", "", $path);

            $menuRouters = MenuRouters::where([
                ["path",  "=", $path],
                ["page_callback",  "=", "views_page"]
            ])->select("page_arguments")->first();
            if (!empty($menuRouters)) {
                $result = unserialize($menuRouters->page_arguments);
                if (!empty($result)) {
                    $viewName = $result[0];
                    $views_display = $result[1];
                    $views_view = ViewsView::where([
                        ["name", "=", $viewName]
                    ])->select('vid')->first();
                    if (!empty($views_view)) {
                        $vid = $views_view->vid;
                        $views_display = ViewsDisplay::where([
                            ["vid", "=", $vid],
                            ["id", "=", $views_display]
                        ])->select("display_options")->first();
                        $display_options = unserialize($views_display->display_options);
                        $filterData = [];
                        if (!empty($display_options["filters"])) {
                            $filterOptions = $display_options["filters"];
                            if (!empty($display_options["exposed_form"])) {
                                $exposedForm = $display_options["exposed_form"];
                                $exposedFormData = $exposedForm["options"]["bef"];
                                $FormDataFilterOptions = [];
                                foreach($exposedFormData as $FormDataKey => $formDataValue){
                                    if($FormDataKey != "general" && $FormDataKey != "sort"){
                                        $FormDataFilterOptions[] = $FormDataKey;
                                    }
                                }
                                foreach($FormDataFilterOptions as $FormDataFilterOptions){
                                    if(!empty($filterOptions[$FormDataFilterOptions])){
                                        if(!empty($filterOptions[$FormDataFilterOptions]["exposed"])){
                                            if(!empty($filterOptions[$FormDataFilterOptions]["vocabulary"])){
                                                $vocabulary = $filterOptions[$FormDataFilterOptions]["vocabulary"];
                                                $TaxonomyVocabulary = TaxonomyVocabulary::join('taxonomy_term_data', function ($join) {
                                                    $join->on('taxonomy_term_data.vid', '=', 'taxonomy_vocabulary.vid');
                                                })->where([
                                                    ["taxonomy_vocabulary.machine_name" , "=" , $vocabulary]
                                                ])
                                                ->select("taxonomy_term_data.tid" , "taxonomy_term_data.name" , DB::raw("taxonomy_vocabulary.name as display_name"))
                                                ->orderBy("taxonomy_term_data.weight")
                                                ->get();
                                                if($TaxonomyVocabulary->first()){
                                                    $tempfilters = [];
                                                    foreach($TaxonomyVocabulary as $TaxonomyVocabularies){
                                                        $tempfiltersData["name"] = $TaxonomyVocabularies->name;
                                                        $tempfiltersData["id"] = $TaxonomyVocabularies->tid;
                                                        $tempfilters[] = $tempfiltersData; 
                                                    }
                                                    $temp["data"] = $tempfilters;
                                                    $temp["display_name"] = $TaxonomyVocabulary[0]->display_name;
                                                    $temp["field"] = $filterOptions[$FormDataFilterOptions]["field"];
                                                    $filterData[] = $temp;
                                                }
                                            }else{
                                                $labelData = $filterOptions[$FormDataFilterOptions]['expose']['label'];
                                                $labelArray = explode("(",$labelData);
                                                $label = trim($labelArray[0]);
                                                $tableName = $filterOptions[$FormDataFilterOptions]['table'];
                                                $fieldName = $filterOptions[$FormDataFilterOptions]['field'];
                                                $FieldTableData = DB::connection('bakingo_mysql')->table($tableName)->select($fieldName)->groupBy($fieldName)->get();
                                                if($FieldTableData->first()){
                                                    $tempfilters = [];
                                                    foreach($FieldTableData as $FieldTableDatas){
                                                        $tempfiltersData["name"] = ucfirst($FieldTableDatas->$fieldName);
                                                        $tempfiltersData["id"] = $FieldTableDatas->$fieldName;
                                                        $tempfilters[] = $tempfiltersData; 
                                                    }
                                                    $temp["data"] = $tempfilters;
                                                    $temp["display_name"] = $label;
                                                    $temp["field"] = $filterOptions[$FormDataFilterOptions]["field"];
                                                    $filterData[] = $temp;
                                                }
                                            }
                                        }
                                    }
                                    
                                }
                            }
                        }
                        $sortData = [];
                        if (!empty($display_options["sorts"])) {
                            $sorts = $display_options["sorts"];
                            $tempSort = [];
                            foreach($sorts as $sort){
                                if(!empty($sort["exposed"])){
                                    $tempSort["field"] = $sort['field'];
                                    $tempSort["order"] = !empty($sort['order']) ? $sort['order'] : "ASC";
                                    $tempSort["label"] = $sort['expose']['label'];
                                    $sortData[] = $tempSort;
                                }
                            }
                        }
                        $headerData = [];
                        if (!empty($display_options['header'])) {
                            $headers = $display_options['header'];
                            if (!empty($headers)) {
                                foreach ($headers as $key =>  $header) {
                                    $headerData[$key] = $header["content"];
                                }
                            }
                        }

                        $footerData = [];
                        if (!empty($display_options['footer'])) {
                            $footer = $display_options['footer'];
                            if (!empty($footer)) {
                                foreach ($footer as $key =>  $footers) {
                                    if ($key != "view") {
                                        $footerData[$key] = $footers["content"];
                                    }
                                }
                            }
                        }
                        $metaData = [];
                        if (!empty($display_options['metatags'])) {
                            $metatags = $display_options['metatags']['und'];
                            $metaData['title'] = (!empty($metatags['title']['value']) ? $metatags['title']['value'] : "");
                            $metaData['description'] = (!empty($metatags['description']['value']) ? $metatags['description']['value'] : "");
                            $metaData['keywords'] = (!empty($metatags['keywords']['value']) ? $metatags['keywords']['value'] : "");
                            $metaData['og:type'] = (!empty($metatags['og:type']['value']) ? $metatags['og:type']['value'] : "");
                            $metaData['og:url'] = (!empty($metatags['og:url']['value']) ? $metatags['og:url']['value'] : "");
                            $metaData['og:title'] = (!empty($metatags['og:title']['value']) ? $metatags['og:title']['value'] : "");
                            $metaData['og:description'] = (!empty($metatags['og:description']['value']) ? $metatags['og:description']['value'] : "");
                            $metaData['twitter:url'] = (!empty($metatags['twitter:url']['value']) ? $metatags['twitter:url']['value'] : "");
                            $metaData['twitter:title'] = (!empty($metatags['twitter:title']['value']) ? $metatags['twitter:title']['value'] : "");
                            $metaData['twitter:description'] = (!empty($metatags['twitter:description']['value']) ? $metatags['twitter:description']['value'] : "");
                        }
                        $data = [];
                        $data["is_header"] = !empty($headerData) ?  true : false ;
                        $data["is_footer"] = !empty($footerData) ?  true : false ;
                        $data["is_meta"] = !empty($metaData) ?  true : false ;
                        $data["is_sort"] = !empty($sortData) ?  true : false ;
                        $data["is_fitler"] = !empty($filterData) ?  true : false ;
                        $data["header"] = $headerData;
                        $data["footer"] = $footerData;
                        $data["meta"] = $metaData;
                        $data["sort"] = $sortData;
                        $data["filter"] = $filterData;
                        $response = $this->ResponseComponent->success("Data Found", $data);
                    } else {
                        $response = $this->ResponseComponent->error("No Meta Info For this Page");
                    }
                } else {
                    $response = $this->ResponseComponent->error("No Meta Info For this Page");
                }
            } else {
                $response = $this->ResponseComponent->error("No Meta Info For this Page");
            }
        } catch (Exception $e) {
            $response = $this->ResponseComponent->exception($e->getMessage());
        }
        return response()->json($response);
    }
}
