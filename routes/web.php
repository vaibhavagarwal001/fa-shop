<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// use Illuminate\Routing\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('getlisting', 'ProductsController@getlisting');
Route::get('getlisting/{currentPage}', 'ProductsController@getlisting');

Route::get('getdetails/{nid}', 'ProductsController@getdetails');

// Route::get('getlisting', 'ProductsController@getlisting');

