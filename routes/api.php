<?php

use Illuminate\Http\Request;
use App\Models\Floweraura\PlViewUrlMapping as FlowerauraUrls;
// use App\Models\Bakingo\PlViewUrlMapping as BakingoUrls;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


$PlViewUrlMappings = FlowerauraUrls::select('view_display_url')->get();
// $PlViewUrlMappingsBakingo = BakingoUrls::select('view_display_url')->get();


$merchant_fa = "Floweraura\\";
$merchant_bk = "Bakingo\\";

foreach($PlViewUrlMappings as $PlViewUrlMapping){
    Route::group( array('prefix' => 'floweraura'), function() use ($merchant_fa , $PlViewUrlMapping) {
        Route::get($PlViewUrlMapping->view_display_url, $merchant_fa . 'ProductsController@getlisting'); 
    });
}

// foreach($PlViewUrlMappingsBakingo as $PlViewUrlMappingBakingo){
//     Route::group( array('prefix' => 'bakingo'), function() use ($merchant_bk , $PlViewUrlMappingBakingo) {
//         Route::get($PlViewUrlMappingBakingo->view_display_url, $merchant_bk . 'ProductsController@getlisting'); 
        // Route::get('meta-info/'.$PlViewUrlMappingBakingo->view_display_url, $merchant_bk . 'ProductsController@getMetaInfo');

//     });
// }

Route::group( array('prefix' => 'bakingo'), function() use ($merchant_bk) {
    /** These will remove once the Module for the URL comes */
    Route::get('getlisting', $merchant_bk . 'ProductsController@getlisting');
    Route::get('chocolate-cakes', $merchant_bk . 'ProductsController@getlisting');
    Route::get('photo-cakes', $merchant_bk . 'ProductsController@getlisting');
    Route::get('photo-cakes/{currentPage}', $merchant_bk . 'ProductsController@getlisting');
    Route::get('getlisting/{currentPage}', $merchant_bk . 'ProductsController@getlisting');
    Route::get('meta-info/getlisting', $merchant_bk . 'ProductsController@getMetaInfo');
    Route::get('meta-info/chocolate-cakes', $merchant_bk . 'ProductsController@getMetaInfo');
    Route::get('meta-info/eggless-cakes', $merchant_bk . 'ProductsController@getMetaInfo');
    Route::get('meta-info/cake-delivery', $merchant_bk . 'ProductsController@getMetaInfo');
    Route::get('meta-info/photo-cakes', $merchant_bk . 'ProductsController@getMetaInfo');
    
    /** These will remove once the Module for the URL comes */

    Route::get('getdetails/{nid}', $merchant_bk . 'ProductsController@getdetails');
    Route::get('menu', $merchant_bk . 'MenuController@getMenu');
    Route::get('menu/{menuType}', $merchant_bk . 'MenuController@getMenu');
});

Route::group( array('prefix' => 'floweraura'), function() use ($merchant_fa) {
    // Route::get('getlisting', $merchant_fa . 'ProductsController@getlisting');
    // Route::get('getlisting/{currentPage}', $merchant_fa . 'ProductsController@getlisting');
    Route::get('getdetails/{nid}', $merchant_fa . 'ProductsController@getdetails'); 
});

