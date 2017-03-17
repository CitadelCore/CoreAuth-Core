<?php
/**
 * Class for handling requests of variables set from on-premises servers.
 * This file may not be redistributed or modified outside of TOWER.
 *
 * @author Joseph Marsden <josephmarsden@towerdevs.xyz>
 * @copyright 2017 CoreNIC
 * @license https://central.core/licenses/internal.php
*/

namespace App\Http\Controllers\Handlers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provisioning\CoreLicense;
use App\Http\Controllers\Provisioning\CoreOrganization;
use App\Http\Controllers\Provisioning\CoreServer;
use App\Http\Controllers\Provisioning\CoreUser;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;
use App\Models\Users;

class VarstoreHandler extends Controller {
  static function HandleRequest($request) {
      $clientip = $_SERVER['REMOTE_ADDR'];
      $server = Servers::where('ipaddress', $clientip)->first();
      $organization = Organizations::where('org_name', $request->input('Organization'))->first();
      $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
      if ($request->has('VariableName')) {
        if ($request->input('VariableName') == "apikey") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("apikey"=>$server['apikey']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "production") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("production"=>$server['production']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "allow_2fa") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("allow_2fa"=>$license['allow_2fa']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "allow_extsso") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("allow_extsso"=>$license['allow_extsso']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "allow_riskengine") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("allow_riskengine"=>$license['allow_riskengine']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "allow_api") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("allow_api"=>$license['allow_api']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "li_expiry") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("li_expiry"=>$license['expiry']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "li_comment") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("li_comment"=>$license['comment']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } elseif ($request->input('VariableName') == "li_numservers") {
          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("li_numservers"=>$license['num_servers']));
          header('Content-Type: application/json');
          echo json_encode($response);
        } else {
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Internal security error.", "error_code"=>"security_error"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
  }
}

?>
