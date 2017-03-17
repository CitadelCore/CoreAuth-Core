<?php
/**
 * Class for the provisioning and updating of user metadata.
 * This file may not be redistributed or modified outside of TOWER.
 *
 * @author Joseph Marsden <josephmarsden@towerdevs.xyz>
 * @copyright 2017 CoreNIC
 * @license https://central.core/licenses/internal.php
*/

namespace App\Http\Controllers\Provisioning;

use App\Http\Controllers\Controller;
use App\Models\Licenses;
use App\Models\Organizations;
use App\Models\Servers;
use App\Models\Users;

class CoreUser extends Controller {
  public $server_id;
  public $org_id;
  public $username;
  public $mfa_enabled;
  public $mfa_secret;
  public $riskengine_enabled;
  public $riskengine_level;
  public $riskengine_lastip;
  public $riskengine_lastlogin;

  public function Provision() {
    $user = new Users;
    $user->server_id = $this->server_id;
    $user->org_id = $this->$org_id;
    $user->username = $this->$username;
    $user->mfa_enabled = false;
    $user->riskengine_enabled = false;
    $user->save();
  }

  public function Update() {
    $user = Users::where('username', $this->username)->where('org_id', $this->org_id)->where('server_id', $this->server_id)->first();
    if ($this->server_id != null) { $user->server_id = $this->server_id; };
    if ($this->org_id != null) { $user->org_id = $this->org_id; };
    if ($this->username != null) { $user->username = $this->username; };
    if ($this->mfa_enabled != null) { $user->mfa_enabled = $this->mfa_enabled; };
    if ($this->mfa_secret != null) { $user->mfa_secret = $this->mfa_secret; };
    if ($this->riskengine_enabled != null) { $user->riskengine_enabled = $this->riskengine_enabled; };
    if ($this->riskengine_level != null) { $user->riskengine_level = $this->riskengine_level; };
    if ($this->riskengine_lastip != null) { $user->riskengine_lastip = $this->riskengine_lastip; };
    if ($this->riskengine_lastlogin != null) { $user->riskengine_lastlogin = $this->riskengine_lastlogin; };
    $user->save();
  }

  public function Delete() {
    $user = Users::where('username', $this->username)->first();
    $user->delete();
  }
}

?>
