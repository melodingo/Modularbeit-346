<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Php</title>
</head>
<body>
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
                    return $server->name;
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

    $message = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cpu = intval($_POST['cpu']);
        $ram = intval($_POST['ram']);
        $ssd = intval($_POST['ssd']);
        $vmName = "VM-" . uniqid();

        $vm = new VM($vmName, $cpu, $ram, $ssd);
        $allocatedServer = $omniCloud->allocateVMToBestServer($vm);
        if ($allocatedServer) {
            $message = "VM provisioned successfully on server: $allocatedServer";
        } else {
            $message = "Failed to provision VM. Not enough resources.";
        }
    }
    ?>

    <form action="index.php" method="POST" class="space-y-4">
        <label for="cpu" class="block text-lg font-medium">CPU Cores:</label>
        <select name="cpu" id="cpu" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md">
            <option value="1">1 Core</option>
            <option value="2">2 Cores</option>
            <option value="4">4 Cores</option>
            <option value="8">8 Cores</option>
            <option value="16">16 Cores</option>
        </select>

        <label for="ram" class="block text-lg font-medium">RAM (MB):</label>
        <select name="ram" id="ram" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md">
            <option value="512">512 MB</option>
            <option value="1024">1,024 MB</option>
            <option value="2048">2,048 MB</option>
            <option value="4096">4,096 MB</option>
            <option value="8192">8,192 MB</option>
        </select>

        <label for="ssd" class="block text-lg font-medium">SSD Storage (GB):</label>
        <select name="ssd" id="ssd" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md">
            <option value="10">10 GB</option>
            <option value="20">20 GB</option>
            <option value="40">40 GB</option>
            <option value="80">80 GB</option>
            <option value="240">240 GB</option>
        </select>
        <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 mt-4">Provision VM</button>
    </form>

    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>