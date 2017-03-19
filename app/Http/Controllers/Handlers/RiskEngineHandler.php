<?php
/**
 * Class for handling RiskEngine requests.
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
use App\Models\RiskEngine;
use DateTime;

class RiskEngineHandler extends Controller {

  static function AppendEntry($risktype, $riskdata, $risk, $username, $org_id, $server_id) {
    // Delete RiskEngine records older than a day.
    $date = new DateTime;
    $date->modify("-1 day");
    RiskEngine::where('username', $username)->where('org_id', $org_id)->where('server_id', $server_id)->where('event_type', $risktype)->where('created_at', '<', $date->format('Y-m-d H:i:s'))->delete();
    // Add our new record.
    $riskengine = new RiskEngine;
    $riskengine->username = $username;
    $riskengine->org_id = $org_id;
    $riskengine->server_id = $server_id;
    $riskengine->event_type = $risktype;
    $riskengine->event_data = $riskdata;
    $riskengine->event_risk = $risk;
    $riskengine->save();

    // Return successful
    $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Sucessfully added RiskEngine entry.", "response_code"=>"riskengine_success"));
    header('Content-Type: application/json');
    echo json_encode($response);
  }

  static function DoMath($engine, $organization, $server, $user, $license) {
    $level = 0;
    $iterations = array();
    $recent = array();
    foreach ($engine as $entry) {
      $type = $entry['event_type'];
      $recent[$type] = false;
    }

    foreach ($engine as $entry) {
      $type = $entry['event_type'];
      if (isset($iterations[$type])) {  } else { $iterations[$type] = 0; };
      switch ($entry['event_type']) {
        case "ip_loggedin":
        $engine_recent = RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->where('event_type', $entry['event_type'])->first();
        if ($recent[$type] == false) {
          if ($entry['event_data'] != $engine_recent) {
            $level = $level + $entry['event_risk'];
          } else {
            $level = $level + $entry['event_risk'];
            $level = $level - 60;
          }
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
          $recent[$type] = true;
        }
        break;
        case "last_location":
        $engine_recent = RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->where('event_type', $entry['event_type'])->first();
        if ($recent[$type] == false) {
          if ($entry['event_data'] != $engine_recent) {
            $level = $level + $entry['event_risk'];
          } else {
            $level = $level + $entry['event_risk'];
            $level = $level - 30;
          }
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
          $recent[$type] = true;
        }
        break;
        case "login_time":
        self::AppendEntry($risk, $riskdata, "0", $username, $organization['org_id'], $server['server_id']);
        break;
        case "mfa_denied":
        $engine_recent = RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->where('event_type', $entry['event_type'])->first();
        if ($iterations[$type] != null) {
          $level = $level + $entry['event_risk'];
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
        }
        break;
        case "mfa_accepted":
        if ($iterations[$type] != null) {
          $level = $level + $entry['event_risk'];
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
        }
        break;
        case "login_deniedon":
        if ($iterations[$type] != null) {
          $level = $level + $entry['event_risk'];
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
        }
        break;
        case "login_acceptedon":
        if ($iterations[$type] != null) {
          $level = $level + $entry['event_risk'];
          $iterations[$type] = $iterations[$type] + 1;
        } else {
          $iterations[$type] = 1;
        }
        break;
        case "temp_startlogin":
        break;
        case "temp_endlogin":
        break;
        case "recent_browser":
        $engine_recent = RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->where('event_type', $entry['event_type'])->first();
        if ($recent[$type] == false) {
          if ($entry['event_data'] != $engine_recent) {
            $level = $level + $entry['event_risk'];
          } else {
            $level = $level + $entry['event_risk'];
            $level = $level - 15;
          }
          $iterations[$type] = $iterations[$type] + 1;
          $recent[$type] = true;
        } else {
          $iterations[$type] = 1;
        }
        break;
        default:
        // Invalid parameters.
        break;
      }
    }
    return $level;
  }

  static function DoQuery($organization, $server, $user, $license) {
    // Purge stale entries
    $date = new DateTime;
    $date->modify("-1 day");

    // Query the database
    RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->where('created_at', '<', $date->format('Y-m-d H:i:s'))->delete();
    $engine = RiskEngine::where('username', $user['username'])->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->take(50)->get();
    // Do the math

    $level = self::DoMath($engine, $organization, $server, $user, $license);

    // Loop finished, let's check the results.
    if ($level > 25 && $level < 50) {
      // Issue a formal warning.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine warned the login.", "response_code"=>"riskengine_warning"));
      header('Content-Type: application/json');
      echo json_encode($response);
    } elseif ($level > 50 && $level < 75) {
      // Issue a formal warning and ask for 2FA, if the subscription and user support it.
      if ($license['allow_2fa'] == true && $user['mfa_enabled'] == true) {
        // Prompt for 2FA.
        $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine requiring 2FA.", "response_code"=>"riskengine_2fa"));
        header('Content-Type: application/json');
        echo json_encode($response);
      } else {
        // Issue a formal warning.
        $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine warned the login.", "response_code"=>"riskengine_warning"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } elseif ($level > 75) {
      // Block the login.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine blocked the login attempt.", "response_code"=>"riskengine_blocked"));
      header('Content-Type: application/json');
      echo json_encode($response);
    } else {
      // Everything seems ok, allow the login.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine allowed the login.", "response_code"=>"riskengine_allowed"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function QueryEngine($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($license['allow_riskengine'] == true) {
      if ($request->has("Username")) {
        $user = Users::where('username', $request->input("Username"))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
        if ($user != null) {
          if ($user['riskengine_enabled'] == true) {
            self::DoQuery($organization, $server, $user, $license);
          } else {
            $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine not enabled for user.", "response_code"=>"riskengine_disabled"));
            header('Content-Type: application/json');
            echo json_encode($response);
          }
        } else {
          // Provision a new user.
          $newuser = new CoreUser;
          $newuser->server_id = $server['server_id'];
          $newuser->org_id = $organization['org_id'];
          $newuser->username = $request->input("Username");
          $newuser->riskengine_enabled = true;
          $newuser->Provision();

          self::DoQuery($organization, $server, $user, $license);
        }
      }
    } else {
      // No license for RiskEngine.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"No license for RiskEngine.", "response_code"=>"riskengine_nolicense"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function AddQuery($server, $organization, $license, $user, $request) {
    if ($user['riskengine_enabled'] == true) {
      if ($request->has("Risk")) {
        if ($request->has("RiskData")) {
          if ($request->has("Username")) {
            $risk = $request->input("Risk");
            $riskdata = $request->input("RiskData");
            if (strlen($riskdata) <= 200) {
              switch ($risk) {
                case "ip_loggedin":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "last_location":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "login_time":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "mfa_denied":
                return self::AppendEntry($risk, $riskdata, "15", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "mfa_accepted":
                return self::AppendEntry($risk, $riskdata, "-20", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "login_deniedon":
                return self::AppendEntry($risk, $riskdata, "15", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "login_acceptedon":
                return self::AppendEntry($risk, $riskdata, "-10", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "temp_startlogin":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "temp_endlogin":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                case "recent_browser":
                return self::AppendEntry($risk, $riskdata, "0", $user['username'], $organization['org_id'], $server['server_id']);
                break;
                default:
                // Invalid parameters.
                $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid parameters.", "error_code"=>"param_invalid"));
                header('Content-Type: application/json');
                echo json_encode($response);
              }
            } else {
              // Not enough parameters.
              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
              header('Content-Type: application/json');
              echo json_encode($response);
            }
          } else {
            // Not enough parameters.
            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
            header('Content-Type: application/json');
            echo json_encode($response);
          }
        } else {
          // Not enough parameters.
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        // Not enough parameters.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // RiskEngine not enabled on user.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"RiskEngine not enabled for user.", "response_code"=>"riskengine_disabled"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function AddEntry($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($license['allow_riskengine'] == true) {
      if ($request->has("Username")) {
        $user = Users::where('username', $request->input("Username"))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
        if ($user != null) {
          self::AddQuery($server, $organization, $license, $user, $request);
        } else {
          // Provision a new user.
          $newuser = new CoreUser;
          $newuser->server_id = $server['server_id'];
          $newuser->org_id = $organization['org_id'];
          $newuser->username = $request->input("Username");
          $newuser->riskengine_enabled = true;
          $newuser->Provision();

          self::AddQuery($server, $organization, $license, $user, $request);
        }
      } else {
        // Not enough parameters.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // No license for RiskEngine.
      $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"No license for RiskEngine.", "response_code"=>"riskengine_nolicense"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }
}
 ?>
