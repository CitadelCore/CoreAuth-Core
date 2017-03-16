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

use App\Http\Controllers\Handlers\LicenseHandler;
use Illuminate\Http\Request;

Route::post('/ca/v1/licensor', function (Request $request) {
    LicenseHandler::HandleRequest($request);
});
