<?php
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
    public function updateResources($cpu, $ram, $ssd, $allocate){
        if($allocate){
            $this->used_cpu += $cpu;
            $this->used_ram += $ram;
            $this->used_ssd += $ssd;
        }else{
            $this->used_cpu -= $cpu;
            $this->used_ram -= $ram;
            $this->used_ssd -= $ssd;
        }   
    }
}

$servers = [
    new Server('Small', 4, 32768, 4000),
    new Server('Medium', 8, 65536, 8000),
    new Server('Large', 16, 131072, 16000),
];

    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $requestedcpu = (int)$_POST['cpu'];
        $requestedram = (int)$_POST['ram'];
        $requestedssd = (int)$_POST['ssd'];
    }

    foreach($servers as $server){
        if($server->hasEnoughResources($requestedcpu, $requestedram, $requestedssd)){
            $server->updateResources($requestedcpu, $requestedram, $requestedssd);
            echo $server->name;
            break;
        }
    }





?>