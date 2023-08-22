<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\WebScrapingController;

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

$controller_path = 'App\Http\Controllers';

Auth::routes();

// Main Page Route
Route::get('/', $controller_path . '\busca\BuscaRapida@index')->name('busca-rapida');

// Busca rapida
Route::get("/busca-rapida", $controller_path . '\busca\BuscaRapida@index')->name('busca-rapida');
Route::post("/get/busca-rapida", $controller_path . '\busca\BuscaRapida@busca')->name('get-busca-rapida');
Route::get("/get/ajax/busca-rapida", $controller_path . '\busca\BuscaRapida@buscaAjax')->name('get-busca-rapida');
Route::post("/send/products/ajax", $controller_path . '\busca\BuscaRapida@save');