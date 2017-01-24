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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::group(['prefix' => 'account'], function(){
  Route::get('/', 'AccountController@index');
  Route::get('/address', 'AccountController@addAddressPage');
  Route::post('/address/add', 'AccountController@addAddress');
  Route::get('/address/edit/{address_id}', 'AccountController@editAddressPage');
  Route::post('/address/edit/{address_id}', 'AccountController@editAddress');
  Route::get('/edit', 'AccountController@editPage');
  Route::post('/edit', 'AccountController@edit');
});
