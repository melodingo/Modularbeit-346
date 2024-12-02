<?php

class VM {
    public $name;
    public $cpu;
    public $ram;
    public $ssd;

    public function __construct($name, $cpu, $ram, $ssd) {
        $this->name = $name;
        $this->cpu = $cpu;
        $this->ram = $ram;
        $this->ssd = $ssd;
    }
}

class Server {
    public $name;
    public $cpu;
    public $ram;
    public $ssd;
    public $used_cpu = 0;
    public $used_ram = 0;
    public $used_ssd = 0;
    private $vms = [];

    public function __construct($name, $cpu, $ram, $ssd) {
        $this->name = $name;
        $this->cpu = $cpu;
        $this->ram = $ram;
        $this->ssd = $ssd;
    }

    public function hasEnoughResources($cpu, $ram, $ssd) {
        return ($this->cpu - $this->used_cpu >= $cpu) && 
               ($this->ram - $this->used_ram >= $ram) &&
               ($this->ssd - $this->used_ssd >= $ssd);
    }

    public function useResources($cpu, $ram, $ssd) {
        $this->used_cpu += $cpu;
        $this->used_ram += $ram;
        $this->used_ssd += $ssd;
    }

    public function allocateVM(VM $vm) {
        if ($this->hasEnoughResources($vm->cpu, $vm->ram, $vm->ssd)) {
            $this->useResources($vm->cpu, $vm->ram, $vm->ssd);
            $this->vms[] = $vm;
            return true;
        }
        return false;
    }

    public function getVMs() {
        return $this->vms;
    }
}

class OmniCloud {
    private $servers = [];

    public function __construct($servers) {
        $this->servers = $servers;
    }

    public function allocateVMToBestServer(VM $vm) {
        usort($this->servers, function($a, $b) {
            return ($a->used_cpu + $a->used_ram + $a->used_ssd) <=> ($b->used_cpu + $b->used_ram + $b->used_ssd);
        });

        foreach ($this->servers as $server) {
            if ($server->allocateVM($vm)) {
                return true;
            }
        }
        return false;
    }
}

// Define servers
$smallServer = new Server('Small', 4, 32768, 4000);
$mediumServer = new Server('Medium', 8, 65536, 8000);
$bigServer = new Server('Big', 16, 131072, 16000);

$omniCloud = new OmniCloud([$smallServer, $mediumServer, $bigServer]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpu = intval($_POST['cpu']);
    $ram = intval($_POST['ram']);
    $ssd = intval($_POST['ssd']);
    $vmName = "VM-" . uniqid();

    $vm = new VM($vmName, $cpu, $ram, $ssd);
    if ($omniCloud->allocateVMToBestServer($vm)) {
        echo "VM provisioned successfully!";
    } else {
        echo "Failed to provision VM. Not enough resources.";
    }
}
?>