<?php

namespace App\Http\Controllers\Bakingo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\Component\ResponseComponent;
use App\Models\Bakingo\TaxonomyVocabulary;
use App\Models\Bakingo\MenuRouters;
use App\Models\Bakingo\ViewsDisplay;
use App\Models\Bakingo\ViewsView;


class MetaInfoController extends Controller
{

    protected $ResponseComponent;


    public function __construct()
    {
        // $result = DB::connection('bakingo_mysql')->select(DB::raw("set sql_mode=''"));
        $this->ResponseComponent = new ResponseComponent();
    }

    //

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
