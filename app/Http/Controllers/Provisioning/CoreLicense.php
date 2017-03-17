<?php
/**
 * Class for the provisioning and updating of license metadata.
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

class CoreLicense extends Controller {
  public $serial;
  public $key;
  public $comment;
  public $expiry;
  public $org_id;
  public $allow_2fa;
  public $allow_extsso;
  public $allow_riskengine;
  public $allow_api;
  public $disabled;
  public $num_servers;

  public function Provision() {
    $license = new Licenses;
    $license->key = bin2hex(openssl_random_pseudo_bytes(30));
    $license->comment = $this->comment;
    $license->expiry = $this->expiry;
    $license->org_id = $this->org_id;
    $license->allow_2fa = $this->allow_2fa;
    $license->allow_extsso = $this->allow_extsso;
    $license->allow_riskengine = $this->allow_riskengine;
    $license->allow_api = $this->allow_api;
    $license->disabled = $this->disabled;
    $license->num_servers = $this->num_servers;
    $license->save();
  }

  public function Update() {
    $license = Licenses::where('serial', $this->serial)->first();
    if ($this->serial != null) { $license->serial = $this->serial; };
    if ($this->key != null) { $license->key = $this->key; };
    if ($this->comment != null) { $license->comment = $this->comment; };
    if ($this->expiry != null) { $license->expiry = $this->expiry; };
    if ($this->org_id != null) { $license->org_id = $this->org_id; };
    if ($this->allow_2fa != null) { $license->allow_2fa = $this->allow_2fa; };
    if ($this->allow_extsso != null) { $license->allow_extsso = $this->allow_extsso; };
    if ($this->allow_riskengine != null) { $license->allow_riskengine = $this->allow_riskengine; };
    if ($this->allow_api != null) { $license->allow_api = $this->allow_api; };
    if ($this->disabled != null) { $license->disabled = $this->disabled; };
    if ($this->num_servers != null) { $license->num_servers = $this->num_servers; };
    $license->save();
  }

  public function Delete() {
    $license = Licenses::where('serial', $this->serial)->first();
    $license->delete();
  }
}

?>
