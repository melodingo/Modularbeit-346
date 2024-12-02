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

class Server{
    public $name;
    public $cpu;
    public $ram;
    public $ssd;
    public $used_cpu = 0;
    public $used_ram = 0;
    public $used_ssd = 0;

    public function __construct($name, $cpu, $ram, $ssd){
        $this->name = $name;
        $this->cpu = $cpu;
        $this->ram = $ram;
        $this->ssd = $ssd;
    }

    public function hasEnoughResources($cpu, $ram, $ssd){
        return ($this->cpu - $this->used_cpu >= $cpu) && 
        ($this->ram - $this->used_ram >= $ram) &&
        ($this->ssd - $this->used_ssd >= $ssd);
    }

    public function useResources($cpu, $ram, $ssd){
        $this->used_cpu += $cpu;
        $this->used_ram += $ram;
        $this->used_ssd += $ssd;
    }

    public function updateResources($cpu, $ram, $ssd, $allocate= true){
        if($allocate){
            $this->used_cpu += $cpu;
            $this->used_ram += $ram;
            $this->used_ssd += $ssd;
        }else{
            $this->used_cpu = max(0, $this->used_cpu - $cpu);
            $this->used_ram = max(0, $this->used_ram - $ram);
            $this->used_ssd = max(0, $this->used_ssd - $ssd);
        }
    }
    private $vms = [];

    public function allocateVM(VM $vm) {
        if ($this->hasEnoughResources($vm->cpu, $vm->ram, $vm->ssd)) {
            $this->useResources($vm->cpu, $vm->ram, $vm->ssd);
            $this->vms[] = $vm;
            return true;
        }
        return false;
    }

    public function deallocateVM($vmName) {
        foreach ($this->vms as $index => $vm) {
            if ($vm->name === $vmName) {
                $this->updateResources($vm->cpu, $vm->ram, $vm->ssd, false);
                unset($this->vms[$index]);
                return true;
            }
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

    public function deallocateVMFromServer($vmName) {
        foreach ($this->servers as $server) {
            if ($server->deallocateVM($vmName)) {
                return true;
            }
        }
        return false;
    }

    public function calculateRevenue() {
        $cpuCost = [1 => 5, 2 => 10, 4 => 18, 8 => 30, 16 => 45];
        $ramCost = [512 => 5, 1024 => 10, 2048 => 20, 4096 => 40, 8192 => 80, 16384 => 160, 32768 => 320];
        $ssdCost = [10 => 5, 20 => 10, 40 => 20, 80 => 40, 240 => 120, 500 => 250, 1000 => 500];

        $totalRevenue = 0;

        foreach ($this->servers as $server) {
            foreach ($server->getVMs() as $vm) {
                $totalRevenue += $cpuCost[$vm->cpu] + $ramCost[$vm->ram] + $ssdCost[$vm->ssd];
            }
        }

        return $totalRevenue;
    }
}




    $smallServer = new Server('Small', 4, 32768, 4000);
    $mediumServer = new Server('Medium', 8, 65536, 8000);
    $bigServer = new Server('Big', 16, 131072, 16000);
    
    $omniCloud = new OmniCloud([$smallServer, $mediumServer, $bigServer]);
    
    $vm1 = new VM('VM1', 2, 1024, 20);
    $vm2 = new VM('VM2', 4, 2048, 40);
    
    $omniCloud->allocateVMToBestServer($vm1);
    $omniCloud->allocateVMToBestServer($vm2);
    
    echo "Total Revenue: " . $omniCloud->calculateRevenue();


?>