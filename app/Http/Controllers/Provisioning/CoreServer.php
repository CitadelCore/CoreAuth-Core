<?php
/**
 * Class for the provisioning and updating of CoreAuth endpoint metadata.
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

class CoreServer extends Controller {
  public $server_id;
  public $serial;
  public $org_id;
  public $hostname;
  public $ipaddress;
  public $apikey;
  public $production;

  public function Provision() {
    $server = new Servers;
    $server->serial = $this->serial;
    $server->org_id = $this->org_id;
    $server->hostname = $this->hostname;
    $server->ipaddress = $this->ipaddress;
    $server->apikey = $this->apikey;
    $server->production = $this->production;
    $server->save();
  }

  public function Update() {
    $server = Servers::where('server_id', $this->server_id)->first();
    if ($this->server_id != null) { $server->server_id = $this->server_id; };
    if ($this->serial != null) { $server->serial = $this->serial; };
    if ($this->org_id != null) { $server->org_id = $this->org_id; };
    if ($this->hostname != null) { $server->hostname = $this->hostname; };
    if ($this->ipaddress != null) { $server->ipaddress = $this->ipaddress; };
    if ($this->apikey != null) { $server->apikey = $this->apikey; };
    if ($this->production != null) { $server->production = $this->production; };
    $server->save();
  }

  public function Delete() {
    $server = Servers::where('server_id', $this->server_id)->first();
    $server->delete();
  }
}

?>
