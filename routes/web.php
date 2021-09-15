<?php

Route::get('/', 'CommonController@main')->name('main'); //Страница с формой логина

Route::get('/login', [\Promodo\LaravelAzureAuth\Azure::class, 'azure'])->name('login');
Route::get('/logout', [\Promodo\LaravelAzureAuth\Azure::class, 'azurelogout'])->name('logout');
Route::get('/login/azurecallback', [\Promodo\LaravelAzureAuth\Azure::class, 'azurecallback']);

Route::group(['middleware' => ['auth']], function () {
    Route::get('/domains', 'DomainsController@index')->name('domains');
    Route::get('/dr-price', 'DomainsController@averagePriceForDr')->name('dr-price');
    Route::get('/cookies', 'CookieController@show')->name('cookies.show');
    Route::post('/cookies', 'CookieController@update')->name('cookies.update');
});