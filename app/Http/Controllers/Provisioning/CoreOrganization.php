<?php
/**
 * Class for the provisioning and updating of organization metadata.
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

class CoreOrganization extends Controller {
  public $org_id;
  public $org_name;
  public $operator_email;
  public $payment_id;

  public function Provision() {
    $organization = new Licenses;
    $organization->org_name = $this->org_name;
    $organization->operator_email = $this->operator_email;
    $organization->payment_id = $this->payment_id;
    $organization->save();
  }

  public function Update() {
    $organization = Licenses::where('org_id', $this->org_id)->first();
    if ($this->org_id != null) { $organization->org_id = $this->org_id; };
    if ($this->org_name != null) { $organization->org_name = $this->org_name; };
    if ($this->operator_email != null) { $organization->operator_email = $this->operator_email; };
    if ($this->payment_id != null) { $organization->payment_id = $this->payment_id; };
    $organization->save();
  }

  public function Delete() {
    $organization = Licenses::where('org_id', $this->org_id)->first();
    $organization->delete();
  }
}

?>
