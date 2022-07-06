<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/dr-price', ['uses' => 'DomainsController@averagePriceForDr', 'output' => 'json']);
//Данные для 1 домена
Route::get('/domain-data', ['uses' => 'DomainsController@getDomainData']);
//Данные для нескольких доменов
Route::post('/domains-data', ['uses' => 'DomainsController@getDomainData']);
