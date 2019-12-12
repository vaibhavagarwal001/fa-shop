<?php

namespace App\Http\Controllers\Bakingo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Models */
use App\Models\Bakingo\MenuLinks;

/** Components */
use App\Http\Controllers\Component\ResponseComponent;
use Exception;

class MenuController extends Controller
{
    //
    protected $ResponseComponent;
    public function __construct()
    {
        $this->ResponseComponent = new ResponseComponent();

    }
    public function getMenu($menuType = 'all'){
        try{
            $response = [];
            switch($menuType){
                case 'main-menu':
                    $data = $this->prepareMainMenu();
                    break;
                case 'footer' :
                    $data = $this->prepareFooterMenu();
                    break; 
                case 'all':
                    $data["main-menu"] = $this->prepareMainMenu();
                    $data["footer-menu"] = $this->prepareFooterMenu();
                    break;
            }
            if(empty($data)){
                $response = $this->ResponseComponent->error("No Menu Found.");    
            }else{
                $response = $this->ResponseComponent->success("Menu Data Found.",$data);
            }
        }catch(Exception $e){
            $response = $this->ResponseComponent->exception($e->getMessage());
        }
        return response()->json($response);
    }

    public function prepareMainMenu(){
        try{
            $MenuLinks = MenuLinks::where([
                ["module" ,  "=" , "menu"],
                ["hidden" ,"=" ,0],
                ["menu_name", "=" ,"main-menu"]
            ])
            ->get();

            $menu = [];
            
            foreach($MenuLinks as $MenuLink){
                $menuData = [
                    "menu_name" => $MenuLink->menu_name,
                    "link_path"=> $MenuLink->link_path,
                    "router_path"=> $MenuLink->router_path,
                    "link_title"=> $MenuLink->link_title,
                    "has_children"=> $MenuLink->has_children
                ];

                if(!empty($MenuLink->plid)){
                    $menu[$MenuLink->plid]["children"][] = $menuData;
                }else{
                    $menu[$MenuLink->mlid] = $menuData;
                }
            }
            return $menu;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function prepareFooterMenu(){
        $MenuLinks = MenuLinks::where([
            ["module" ,  "=" , "menu"],
            ["hidden" ,"=" ,0]            
        ])
        ->whereIn("menu_name" , ["menu-footer2" , "menu-footer1" , "menu-no-city-footer3"])
        ->get();

        $menu = [];
        
        foreach($MenuLinks as $MenuLink){
            $menuData = [
                "menu_name" => $MenuLink->menu_name,
                "link_path"=> $MenuLink->link_path,
                "router_path"=> $MenuLink->router_path,
                "link_title"=> $MenuLink->link_title,
                "has_children"=> $MenuLink->has_children
            ];
            if(!empty($MenuLink->plid)){
                $menu[$MenuLink->menu_name][$MenuLink->plid]["children"][] = $menuData;
            }else{
                $menu[$MenuLink->menu_name][$MenuLink->mlid] = $menuData;
            }
        }
        return $menu;
    }
}
