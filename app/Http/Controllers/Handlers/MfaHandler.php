<?php
/**
 * Class for handling multi-factor authentication requests.
 * This file may not be redistributed or modified outside of TOWER.
 *
 * @author Joseph Marsden <josephmarsden@towerdevs.xyz>
 * @copyright 2017 CoreNIC
 * @license https://central.core/licenses/internal.php
*/

namespace App\Http\Controllers\Handlers;

use App\Http\Controllers\Handlers\LicenseHandler;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Provisioning\CoreLicense;
use App\Http\Controllers\Provisioning\CoreOrganization;
use App\Http\Controllers\Provisioning\CoreServer;
use App\Http\Controllers\Provisioning\CoreUser;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;
use App\Models\Users;

class MfaHandler extends Controller {
  static function ProvisionMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('username')) {
      $user_test = Users::where('username', $request->input('username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user != null) {
        // Update the existing metadata.
        $user = new CoreUser;
        $user->username = $request->input('username');
        $user->server_id = $server['server_id'];
        $user->org_id = $organization['org_id'];

        $totp = new TOTP(
            $request->input('username'),
            NULL
        );

        $totp->setParameter('image', 'https://central.core/assets/ca/mfa_img.png');
        $totp->setIssuer($organization['org_name']);
        $user->mfa_secret = $totp->getSecret();

        $user->Update();
      } else {
        // Provision a new user metadata store.
        $user = new CoreUser;
        $user->username = $request->input('username');
        $user->server_id = $server['server_id'];
        $user->org_id = $organization['org_id'];

        $user->Provision();
      }
    } else {

    }
  }

  static function CheckMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('username') && $request->has('token')) {
      $user = Users::where('username', $request->input('username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user != null) {
        if ($user['mfa_enabled'] == true) {
          $totp = new TOTP(
              $request->input('username'),
              NULL
          );
          if ($totp->verify($request->input('token')) == true) {
            return true;
          } else {
            return false;
          }
        } elseif ($user['mfa_secret'] == null) {
          // Mfa is ready but not enabled.
        } else {
          // Mfa is not ready at all. Needs to be provisioned.
        }
      } else {
        // No metadata exists! Fail.
      }
    } else {
      // Not enough parameters.
    }
  }

  static function PurgeMfa($request) {

  }
}

?>
