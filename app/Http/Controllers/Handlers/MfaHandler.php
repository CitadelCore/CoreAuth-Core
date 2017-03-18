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

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provisioning\CoreLicense;
use App\Http\Controllers\Provisioning\CoreOrganization;
use App\Http\Controllers\Provisioning\CoreServer;
use App\Http\Controllers\Provisioning\CoreUser;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;
use App\Models\Users;
use OTPHP\TOTP;

class MfaHandler extends Controller {
  static function ProvisionMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('Username')) {
      $user_test = Users::where('username', $request->input('Username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user_test != null) {
        if ($user_test['mfa_secret'] == null) {
          // Update the existing metadata.
          $user = new CoreUser;
          $user->username = $request->input('Username');
          $user->server_id = $server['server_id'];
          $user->org_id = $organization['org_id'];

          $totp = new TOTP(
              $request->input('Username'),
              NULL
          );

          $totp->setParameter('image', 'https://central.core/assets/ca/mfa_img.png');
          $totp->setIssuer($organization['org_name']);
          $user->mfa_secret = $totp->getSecret();

          $user->Update();

          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Successfully provisioned 2FA.", "response_code"=>"2fa_provisioned"));
          header('Content-Type: application/json');
          echo json_encode($response);
        } else {
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"2FA already provisioned.", "error_code"=>"2fa_alreadyprov"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        // Provision a new user metadata store.
        $user = new CoreUser;
        $user->username = $request->input('Username');
        $user->server_id = $server['server_id'];
        $user->org_id = $organization['org_id'];

        $totp = new TOTP(
            $request->input('Username'),
            NULL
        );

        $totp->setParameter('image', 'https://central.core/assets/ca/mfa_img.png');
        $totp->setIssuer($organization['org_name']);
        $user->mfa_secret = $totp->getSecret();

        $user->Provision();

        $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Successfully provisioned 2FA.", "response_code"=>"2fa_provisioned"), "payload"=>array("qrcodeuri"=>$totp->getQrCodeUri(), "enabled"=>$user_test['mfa_enabled']));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function CheckMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('Username') && $request->has('Token')) {
      $user = Users::where('username', $request->input('Username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user != null) {
        if ($user['mfa_enabled'] == true) {
          $totp = new TOTP(
              $request->input('Username'),
              $user['mfa_secret']
          );
          if ($totp->verify($request->input('Token')) == true) {
            $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"2FA token is valid.", "response_code"=>"2fa_valid"));
            header('Content-Type: application/json');
            echo json_encode($response);
          } else {
            $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"2FA token is invalid.", "error_code"=>"2fa_invalid"));
            header('Content-Type: application/json');
            echo json_encode($response);
          }
        } elseif ($user['mfa_secret'] == null) {
          // Mfa is ready but not enabled.
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"2FA is not enabled.", "error_code"=>"2fa_disabled"));
          header('Content-Type: application/json');
          echo json_encode($response);
        } else {
          // Mfa is not ready at all. Needs to be provisioned.
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No metadata exists.", "error_code"=>"2fa_nodata"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        // No metadata exists! Fail.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No user metadata exists.", "error_code"=>"2fa_user_nodata"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // Not enough parameters.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function PurgeMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('Username')) {
      $user_test = Users::where('username', $request->input('Username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user_test != null) {
        $user = new CoreUser;
        $user->username = $request->input('Username');
        $user->server_id = $server['server_id'];
        $user->org_id = $organization['org_id'];

        $user->mfa_secret = null;
        $user->mfa_enabled = false;

        $user->Update();

        $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"2FA token purged successfully.", "response_code"=>"2fa_purged"));
        header('Content-Type: application/json');
        echo json_encode($response);
      } else {
        // No metadata exists! Fail.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No user metadata exists.", "error_code"=>"2fa_user_nodata"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // Not enough parameters.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function GetInfo($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('Username')) {
      $user = Users::where('username', $request->input('Username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user != null) {
        $totp = new TOTP(
            $request->input('Username'),
            $user['mfa_secret']
        );
        $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Success.", "response_code"=>"query_accepted"), "payload"=>array("qrcodeuri"=>$totp->getQrCodeUri(), "enabled"=>$user['mfa_enabled']));
        header('Content-Type: application/json');
        echo json_encode($response);
      } else {
        // No metadata exists! Fail.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No user metadata exists.", "error_code"=>"2fa_user_nodata"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // Not enough parameters.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }

  static function EnableMfa($request) {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $server = Servers::where('ipaddress', $clientip)->first();
    $organization = Organizations::where('org_name', $request->input('Organization'))->first();
    $license = Licenses::where('serial', $request->input('LicenseSerial'))->first();
    if ($request->has('Username') && $request->has('Token')) {
      $user = Users::where('username', $request->input('Username'))->where('org_id', $organization['org_id'])->where('server_id', $server['server_id'])->first();
      if ($user != null) {
        $totp = new TOTP(
            $request->input('Username'),
            $user['mfa_secret']
        );
        if ($totp->verify($request->input('Token')) == true) {
          $user = new CoreUser;
          $user->username = $request->input('Username');
          $user->server_id = $server['server_id'];
          $user->org_id = $organization['org_id'];
          $user->mfa_enabled = true;

          $user->Update();

          $response = array("type"=>"response", "id"=>"1", "attributes"=>array("response_friendly"=>"Successfully enabled and enforced 2FA.", "response_code"=>"2fa_enabled"));
          header('Content-Type: application/json');
          echo json_encode($response);
        } else {
          $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"2FA token is invalid.", "error_code"=>"2fa_invalid"));
          header('Content-Type: application/json');
          echo json_encode($response);
        }
      } else {
        // No metadata exists! Fail.
        $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"No user metadata exists.", "error_code"=>"2fa_user_nodata"));
        header('Content-Type: application/json');
        echo json_encode($response);
      }
    } else {
      // Not enough parameters.
      $response = array("type"=>"error", "id"=>"1", "attributes"=>array("error_friendly"=>"Not enough parameters.", "error_code"=>"param_error"));
      header('Content-Type: application/json');
      echo json_encode($response);
    }
  }
}
?>
