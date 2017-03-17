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
use App\Http\Controllers\Handlers\VarstoreHandler;
use Illuminate\Http\Request;

Route::post('/ca/v1/licensor', function (Request $request) {
    LicenseHandler::HandleRequest($request, function(){
      // Return success.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"key_accepted"));
      header('Content-Type: application/json');
      echo json_encode($response);
    });
});

Route::post('/ca/v1/varstore', function (Request $request) {
  VarstoreHandler::HandleRequest($request);
});

Route::post('/ca/v1/mfaenbl', function (Request $request) {
  MfaHandler::EnableMfa($request);
});

Route::post('/ca/v1/mfaprov', function (Request $request) {
  MfaHandler::ProvisionMfa($request);
});

Route::post('/ca/v1/mfaprge', function (Request $request) {
  MfaHandler::PurgeMfa($request);
});

Route::post('/ca/v1/mfainfo', function (Request $request) {
  MfaHandler::ProvisionMfa($request);
});
