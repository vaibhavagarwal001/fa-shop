<?php

use Illuminate\Http\Request;

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

// Route::get('getlisting', 'ProductsController@getlisting');
// Route::get('getlisting/{currentPage}', 'ProductsController@getlisting');

// Route::get('bakingo/getdetails/{nid}', 'Bakingo\ProductsController@getdetails');

$merchant_fa = "Floweraura\\";
$merchant_bk = "Bakingo\\";

Route::group( array('prefix' => 'bakingo'), function() use ($merchant_bk) {
    Route::get('getlisting', $merchant_bk . 'ProductsController@getlisting');
    Route::get('getlisting/{currentPage}', $merchant_bk . 'ProductsController@getlisting');
    Route::get('getdetails/{nid}', $merchant_bk . 'ProductsController@getdetails'); 
});

Route::group( array('prefix' => 'floweraura'), function() use ($merchant_fa) {
    // Route::get('getlisting', $merchant_fa . 'ProductsController@getlisting');
    // Route::get('getlisting/{currentPage}', $merchant_fa . 'ProductsController@getlisting');
    Route::get('getdetails/{nid}', $merchant_fa . 'ProductsController@getdetails'); 
});