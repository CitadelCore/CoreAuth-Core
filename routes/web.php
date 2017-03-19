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
use App\Http\Controllers\Handlers\MfaHandler;
use App\Http\Controllers\Handlers\RiskEngineHandler;
use Illuminate\Http\Request;

//use App\Http\Controllers\Provisioning\CoreOrganization;
//use App\Http\Controllers\Provisioning\CoreLicense;
//use App\Models\Organizations;

/*Route::post('/dev/test_setup', function (Request $request) {
  $organization = new CoreOrganization;
  $organization->org_name = "CoreNIC";
  $organization->operator_email = "josephmarsden@towerdevs.xyz";
  $organization->payment_id = 1;
  $organization->Provision();

  $org_temp = Organizations::where('org_name', $request->input('Organization'))->first();

  $license = new CoreLicense;
  $license->comment = "Development license.";
  $license->expiry = "9999-01-01 00:00:00";
  $license->org_id = $org_temp['org_id'];
  $license->allow_2fa = true;
  $license->allow_extsso = true;
  $license->allow_riskengine = true;
  $license->allow_api = true;
  $license->disabled = false;
  $license->num_servers = 999;
  $license->Provision();

  $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"key_accepted"));
  header('Content-Type: application/json');
  echo json_encode($response);
}); //->middleware('licensed');*/

Route::post('/ca/v1/licensor', function (Request $request) {
  // Return success.
  $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"key_accepted"));
  header('Content-Type: application/json');
  echo json_encode($response);
})->middleware('licensed');

Route::post('/ca/v1/varstore', function (Request $request) {
  VarstoreHandler::HandleRequest($request);
})->middleware('licensed');

Route::post('/ca/v1/deluser', function (Request $request) {
  VarstoreHandler::DeleteUser($request);
})->middleware('licensed');

Route::post('/ca/v1/mfaprov', function (Request $request) {
  MfaHandler::ProvisionMfa($request);
})->middleware('licensed');

Route::post('/ca/v1/mfaenbl', function (Request $request) {
  MfaHandler::EnableMfa($request);
})->middleware('licensed');

Route::post('/ca/v1/mfaprge', function (Request $request) {
  MfaHandler::PurgeMfa($request);
})->middleware('licensed');

Route::post('/ca/v1/mfainfo', function (Request $request) {
  MfaHandler::GetInfo($request);
})->middleware('licensed');

Route::post('/ca/v1/mfacheck', function (Request $request) {
  MfaHandler::CheckMfa($request);
})->middleware('licensed');

Route::post('/ca/v1/re/append', function (Request $request) {
  RiskEngineHandler::AddEntry($request);
})->middleware('licensed');

Route::post('/ca/v1/re/query', function (Request $request) {
  RiskEngineHandler::QueryEngine($request);
})->middleware('licensed');
