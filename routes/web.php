<?php

use App\Services\Parsers\Collaborator;
use Illuminate\Support\Facades\Route;
use Promodo\LaravelAzureAuth\Azure;

Route::get('/', 'CommonController@main')->name('main'); //Страница с формой логина

Route::get('/login', [Azure::class, 'azure'])->name('login');
Route::get('/logout', [Azure::class, 'azurelogout'])->name('logout');
Route::get('/login/azurecallback', [\Promodo\LaravelAzureAuth\Azure::class, 'azurecallback']);

Route::group(['middleware' => ['auth']], function () {
    Route::get('/domains', [\App\Http\Controllers\DomainsController::class,'index'])->name('domains');
    Route::get('/dr-price', 'DomainsController@averagePriceForDr')->name('dr-price');
    Route::get('/cookies', 'CookieController@show')->name('cookies.show');
    Route::post('/cookies', 'CookieController@update')->name('cookies.update');
});

Route::get('/test', function () {
    $parser = new \App\Services\Parsers\Prposting();
    $html = file_get_contents(storage_path('logs/parsers/prposting/pages/row.html'));
    $parser->fetchDomainData($html);

});