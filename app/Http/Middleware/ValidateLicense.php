<?php
/**
 * Middleware for authenticating CoreAuth servers.
 * This file may not be redistributed or modified outside of TOWER.
 *
 * @author Joseph Marsden <josephmarsden@towerdevs.xyz>
 * @copyright 2017 CoreNIC
 * @license https://central.core/licenses/internal.php
*/

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Provisioning\CoreLicense;
use App\Http\Controllers\Provisioning\CoreOrganization;
use App\Http\Controllers\Provisioning\CoreServer;
use App\Http\Controllers\Provisioning\CoreUser;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;
use App\Models\Users;

class ValidateLicense {
  public function handle($request, Closure $next) {
    $organizations = new Organizations;
    $clientip = $_SERVER['REMOTE_ADDR'];
    if ($request->has("Organization")) {
      if ($request->has("OrganizationKey")) {
        if ($request->has("LicenseSerial")) {
          if ($request->has("LicenseKey")) {
            if ($request->has("ServerHostname")) {
              $organization = Organizations::where('org_name', $request->input('Organization'))->first();
              if ($organization != null) {
                // Organization name is valid.
                if ($organization['org_key'] == $request->input('OrganizationKey')) {
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
                                  return $next($request);
                                } else {
                                  // Hostname does not match
                                  $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Internal security error.", "error_code"=>"security_error"));
                                  header('Content-Type: application/json');
                                  return response(json_encode($response));

                                }
                              } else {
                                // Serial does not match
                                $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Internal security error.", "error_code"=>"security_error"));
                                header('Content-Type: application/json');
                                return response(json_encode($response));
                              }
                            } else {
                              // All server slots are used up!
                              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No license slots left.", "error_code"=>"key_noslots"));
                              header('Content-Type: application/json');
                              return response(json_encode($response));
                            }
                          } else {
                            // License is disabled
                            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License disabled.", "error_code"=>"key_disabled"));
                            header('Content-Type: application/json');
                            return response(json_encode($response));
                          }
                        } else {
                          // License has expired
                          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License expired.", "error_code"=>"key_expired"));
                          header('Content-Type: application/json');
                          return response(json_encode($response));
                        }
                     } else {
                       // License key is not valid.
                       $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license key.", "error_code"=>"key_error"));
                       header('Content-Type: application/json');
                       return response(json_encode($response));
                     }
                   } else {
                     $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license serial.", "error_code"=>"serial_error"));
                     header('Content-Type: application/json');
                     return response(json_encode($response));
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
                              $server = new CoreServer;

                              $server->serial = $request->input('LicenseSerial');
                              $server->org_id = $organization['org_id'];
                              $server->hostname = $request->input('ServerHostname');
                              $server->ipaddress = $clientip;
                              $server->apikey = bin2hex(openssl_random_pseudo_bytes(15));
                              $server->production = true;

                              // Provision the new server.
                              $server->Provision();

                              return $next($request);

                            } else {
                              // All server slots are used up!
                              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No license slots left.", "error_code"=>"key_noslots"));
                              header('Content-Type: application/json');
                              return response(json_encode($response));
                            }
                          } else {
                            // License is disabled
                            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License disabled.", "error_code"=>"key_disabled"));
                            header('Content-Type: application/json');
                            return response(json_encode($response));
                          }
                        } else {
                          // License has expired
                          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"License expired.", "error_code"=>"key_expired"));
                          header('Content-Type: application/json');
                          return response(json_encode($response));
                        }
                      } else {
                        // License key is not valid.
                        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license key.", "error_code"=>"key_error"));
                        header('Content-Type: application/json');
                        return response(json_encode($response));
                      }
                    } else {
                      // License serial is not valid.
                      //self::CreateLicense("Development license key.", "9999-01-01 00:00:00", 1, true, true, true, true, false, 999);
                      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid license serial.", "error_code"=>"serial_error"));
                      header('Content-Type: application/json');
                      return response(json_encode($response));
                    }
                  }
                } else {
                  $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid organization key.", "error_code"=>"org_key_error"));
                  header('Content-Type: application/json');
                  return response(json_encode($response));
                }
              } else {
                // Organization is not valid.
                //self::CreateOrganization("CoreNIC", "josephmarsden@towerdevs.xyz", 1);
                $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Invalid organization.", "error_code"=>"org_error"));
                header('Content-Type: application/json');
                return response(json_encode($response));
              }
            } else {
              // Server hostname not provided.
              $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
              header('Content-Type: application/json');
              return response(json_encode($response));
            }
          } else {
            // License key not provided.
            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
            header('Content-Type: application/json');
            return response(json_encode($response));
          }
        } else {
          // License serial not provided.
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
          header('Content-Type: application/json');
          return response(json_encode($response));
        }
      } else {
        // Organization key not provided.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
        header('Content-Type: application/json');
        return response(json_encode($response));
      }
    } else {
      // Organization not provided.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      return response(json_encode($response));
    }
  }
}
 ?>
