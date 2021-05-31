<?php

Route::get('/', 'CommonController@main')->name('main'); //Страница с формой логина
Route::get('/login', '\App\Http\Middleware\AppAzure@azure')->name('login'); //Обработчик логина через азур
Route::get('/logout', '\App\Http\Middleware\AppAzure@logout')->name('logout');
Route::get('/login/azurecallback', '\App\Http\Middleware\AppAzure@azurecallback');

Route::group(['middleware' => ['azure']], function () {
    Route::get('/domains', 'DomainsController@index')->name('domains');
    Route::get('/dr-price', 'DomainsController@averagePriceForDr')->name('dr-price');
    Route::get('/cookies', 'CookieController@show')->name('cookies.show');
    Route::post('/cookies', 'CookieController@update')->name('cookies.update');
});
