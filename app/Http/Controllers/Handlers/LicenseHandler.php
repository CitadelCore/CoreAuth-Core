<?php
/**
 * Class for handling license validation requests from on-prem CoreAuth servers.
 * This file may not be redistributed or modified outside of TOWER.
 *
 * @author Joseph Marsden <josephmarsden@towerdevs.xyz>
 * @copyright 2017 CoreNIC
 * @license https://central.core/licenses/internal.php
*/

namespace App\Http\Controllers\Handlers;

use App\Http\Controllers\Controller;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;

class LicenseHandler extends Controller {
  static function HandleRequest($request) {
    $organizations = new Organizations;
    $clientip = $_SERVER['REMOTE_ADDR'];
    if ($request->has("Organization")) {
      if ($request->has("LicenseSerial")) {
        if ($request->has("LicenseKey")) {
          if ($request->has("ServerHostname")) {
            $organization = Organizations::where('org_name', $request->input('Organization'))->first();
            if ($organization != null) {
              // Organization name is valid.
              $server = Servers::where('ipaddress', $clientip)->first();
              if ($server != null) {
                // Server is present in database.
                $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
                if ($license != null) {
                  // License serial is valid.
                  if ($license['key'] == $request->input('LicenseKey')) {
                    if (strtotime($license['expiry']) > strtotime(date('y-m-d h:m:s'))) {
                      if ($license['disabled'] == false) {
                        if (Servers::where('serial', $license['serial'])->count() <= $license['num_servers']) {
                          if ($server['serial'] == $license['serial']) {
                            if ($server['hostname'] == $request->input('ServerHostname')) {
                              // Return success.
                              $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"key_accepted"));
                              header('Content-Type: application/json');
                              echo json_encode($response);
                            } else {
                              // Hostname does not match
                              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Internal security error.", "error_code"=>"security_error"));
                              header('Content-Type: application/json');
                              echo json_encode($response);
                            }
                          } else {
                            // Serial does not match
                            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Internal security error.", "error_code"=>"security_error"));
                            header('Content-Type: application/json');
                            echo json_encode($response);
                          }
                        } else {
                          // All server slots are used up!
                          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No license slots left.", "error_code"=>"key_noslots"));
                          header('Content-Type: application/json');
                          echo json_encode($response);
                        }
                      } else {
                        // License is disabled
                        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License disabled.", "error_code"=>"key_disabled"));
                        header('Content-Type: application/json');
                        echo json_encode($response);
                      }
                    } else {
                      // License has expired
                      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License expired.", "error_code"=>"key_expired"));
                      header('Content-Type: application/json');
                      echo json_encode($response);
                    }
                 } else {
                   // License key is not valid.
                   $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license key.", "error_code"=>"key_error"));
                   header('Content-Type: application/json');
                   echo json_encode($response);
                 }
               } else {
                 $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license serial.", "error_code"=>"serial_error"));
                 header('Content-Type: application/json');
                 echo json_encode($response);
               }
              } else {
                // Server is not present in database, let's add it.
                $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
                if ($license != null) {
                  // License serial is valid.
                  if ($license['key'] == $request->input('LicenseKey')) {
                    if (strtotime($license['expiry']) > strtotime(date('y-m-d h:m:s'))) {
                      if ($license['disabled'] == false) {
                        if (Servers::where('serial', $license['serial'])->count() < $license['num_servers']) {
                          // License key is valid. Let's create the new server.
                          $servers = new Servers;

                          $servers->serial = $request->input('LicenseSerial');
                          $servers->org_id = $organization['org_id'];
                          $servers->hostname = $request->input('ServerHostname');
                          $servers->ipaddress = $clientip;
                          $servers->apikey = bin2hex(openssl_random_pseudo_bytes(15));
                          $servers->production = true;

                          // Save the new server to the database.
                          $servers->save();

                          // Return success.
                          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"key_accepted"));
                          header('Content-Type: application/json');
                          echo json_encode($response);

                        } else {
                          // All server slots are used up!
                          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No license slots left.", "error_code"=>"key_noslots"));
                          header('Content-Type: application/json');
                          echo json_encode($response);
                        }
                      } else {
                        // License is disabled
                        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License disabled.", "error_code"=>"key_disabled"));
                        header('Content-Type: application/json');
                        echo json_encode($response);
                      }
                    } else {
                      // License has expired
                      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License expired.", "error_code"=>"key_expired"));
                      header('Content-Type: application/json');
                      echo json_encode($response);
                    }
                  } else {
                    // License key is not valid.
                    $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license key.", "error_code"=>"key_error"));
                    header('Content-Type: application/json');
                    echo json_encode($response);
                  }
                } else {
                  // License serial is not valid.
                  //self::CreateLicense("Development license key.", "9999-01-01 00:00:00", 1, true, true, true, true, false, 999);
                  $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license serial.", "error_code"=>"serial_error"));
                  header('Content-Type: application/json');
                  echo json_encode($response);
                }
              }
            } else {
              // Organization is not valid.
              //self::CreateOrganization("CoreNIC", "josephmarsden@towerdevs.xyz", 1);
              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid organization.", "error_code"=>"org_error"));
              header('Content-Type: application/json');
              echo json_encode($response);
            }
          } else {
            // Server hostname not provided.
            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
            header('Content-Type: application/json');
            echo json_encode($response);
          }
        } else {
          // License key not provided.
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        // License serial not provided.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // Organization not provided.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function CreateOrganization($name, $email, $paymentid) {
    $organization = new Organizations;
    $organization->org_name = $name;
    $organization->operator_email = $email;
    $organization->payment_id = $paymentid;
    $organization->save();
  }

  static function CreateLicense($comment, $expiry, $org_id, $allow2fa, $allowsso, $allowre, $allowapi, $disabled, $num_servers) {
    $license = new Licenses;
    $license->key = bin2hex(openssl_random_pseudo_bytes(30));
    $license->comment = $comment;
    $license->expiry = $expiry;
    $license->org_id = $org_id;
    $license->allow_2fa = $allow2fa;
    $license->allow_extsso = $allowsso;
    $license->allow_riskengine = $allowre;
    $license->allow_api = $allowapi;
    $license->disabled = $disabled;
    $license->num_servers = $num_servers;
    $license->save();
  }
}
 ?>
