<?php
class VM {
    public $name;
    public $cpu;
    public $ram;
    public $ssd;
    public $price;

    public function __construct($name, $cpu, $ram, $ssd, $cpuCost, $ramCost, $ssdCost) {
        $this->name = $name;
        $this->cpu = $cpu;
        $this->ram = $ram;
        $this->ssd = $ssd;
        $this->price = $cpuCost + $ramCost + $ssdCost;
    }
}

class Server {
    public $name;
    private $total_cpu;
    private $total_ram;
    private $total_ssd;
    public $used_cpu = 0;
    public $used_ram = 0;
    public $used_ssd = 0;
    private $vms = [];

    public function __construct($name, $cpu, $ram, $ssd) {
        $this->name = $name;
        $this->total_cpu = $cpu;
        $this->total_ram = $ram;
        $this->total_ssd = $ssd;
    }

    public function canAllocate(VM $vm) {
        return ($this->total_cpu - $this->used_cpu >= $vm->cpu) &&
               ($this->total_ram - $this->used_ram >= $vm->ram) &&
               ($this->total_ssd - $this->used_ssd >= $vm->ssd);
    }

    public function allocateVM(VM $vm) {
        if ($this->canAllocate($vm)) {
            $this->used_cpu += $vm->cpu;
            $this->used_ram += $vm->ram;
            $this->used_ssd += $vm->ssd;
            $this->vms[$vm->name] = $vm;
            return true;
        }
        return false;
    }

    public function hasVM($vmName) {
        return isset($this->vms[$vmName]);
    }

    public function removeVM($vmName) {
        if ($this->hasVM($vmName)) {
            $vm = $this->vms[$vmName];
            $this->used_cpu -= $vm->cpu;
            $this->used_ram -= $vm->ram;
            $this->used_ssd -= $vm->ssd;
            unset($this->vms[$vmName]);
        }
    }

    public function getTotalRevenue() {
        $totalRevenue = 0;
        foreach ($this->vms as $vm) {
            $totalRevenue += $vm->price;
        }
        return $totalRevenue;
    }
}

class OmniCloud {
    private $servers;

    public function __construct($servers) {
        $this->servers = $servers;
    }

    public function allocateVMToBestServer(VM $vm) {
        // Check if any server has enough resources
        $suitableServers = array_filter($this->servers, function($server) use ($vm) {
            return $server->canAllocate($vm);
        });

        if (empty($suitableServers)) {
            return false; // No server can allocate the VM
        }

        // Sort servers by their current resource usage
        usort($suitableServers, function($a, $b) {
            return ($a->used_cpu + $a->used_ram + $a->used_ssd) <=> ($b->used_cpu + $b->used_ram + $b->used_ssd);
        });

        // Allocate VM to the best server
        foreach ($suitableServers as $server) {
            if ($server->allocateVM($vm)) {
                return $server->name;
            }
        }

        return false;
    }

    public function removeVMFromServer($vmName) {
        foreach ($this->servers as $server) {
            if ($server->hasVM($vmName)) {
                $server->removeVM($vmName);
                return true;
            }
        }
        return false;
    }

    public function getTotalRevenue() {
        $totalRevenue = 0;
        foreach ($this->servers as $server) {
            $totalRevenue += $server->getTotalRevenue();
        }
        return $totalRevenue;
    }
}

// Define servers
$smallServer = new Server('Small', 4, 32768, 4000);
$mediumServer = new Server('Medium', 8, 65536, 8000);
$bigServer = new Server('Big', 16, 131072, 16000);

$omniCloud = new OmniCloud([$smallServer, $mediumServer, $bigServer]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ob_start(); // Start output buffering
    $response = ['message' => ''];

    if (isset($_POST['cpu']) && isset($_POST['ram']) && isset($_POST['ssd'])) {
        $cpu = intval($_POST['cpu']);
        $ram = intval($_POST['ram']);
        $ssd = intval($_POST['ssd']);
        $vmName = "VM-" . uniqid();

        // Define costs based on the form options
        $cpuCost = 0;
        $ramCost = 0;
        $ssdCost = 0;

        switch ($cpu) {
            case 1:
                $cpuCost = 5;
                break;
            case 2:
                $cpuCost = 10;
                break;
            case 4:
                $cpuCost = 18;
                break;
            case 8:
                $cpuCost = 30;
                break;
            case 16:
                $cpuCost = 45;
                break;
        }

        switch ($ram) {
            case 512:
                $ramCost = 5;
                break;
            case 1024:
                $ramCost = 10;
                break;
            case 2048:
                $ramCost = 20;
                break;
            case 4096:
                $ramCost = 40;
                break;
            case 8192:
                $ramCost = 80;
                break;
            case 16384:
                $ramCost = 160;
                break;
            case 32768:
                $ramCost = 320;
                break;
        }

        switch ($ssd) {
            case 20:
                $ssdCost = 5;
                break;
            case 40:
                $ssdCost = 10;
                break;
            case 80:
                $ssdCost = 20;
                break;
            case 240:
                $ssdCost = 60;
                break;
            case 500:
                $ssdCost = 125;
                break;
            case 1000:
                $ssdCost = 250;
                break;
        }

        $vm = new VM($vmName, $cpu, $ram, $ssd, $cpuCost, $ramCost, $ssdCost);
        $allocatedServer = $omniCloud->allocateVMToBestServer($vm);
        if ($allocatedServer) {
            $response['message'] = "VM provisioned successfully on server: $allocatedServer. VM Price: " . $vm->price . " CHF";
            $response['total_revenue'] = $omniCloud->getTotalRevenue();
        } else {
            $response['message'] = "No server has enough resources to allocate the VM.";
        }
    }

    ob_end_clean(); // Clean the output buffer
    echo json_encode($response);
    exit(); // Ensure no further output is sent
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modularbeit M346</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/input.css" rel="stylesheet">
    <link href="assets/css/animate.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function provisionVM(event) {
            event.preventDefault();
            const cpu = document.getElementById('cpu').value;
            const ram = document.getElementById('ram').value;
            const ssd = document.getElementById('ssd').value;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cpu=${cpu}&ram=${ram}&ssd=${ssd}`
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('message').innerText = data.message;
                if (data.total_revenue) {
                    document.getElementById('total_revenue').innerText = `Total Revenue: $${data.total_revenue}`;
                }
            });
        }
    </script>
</head>
<body class="bg-neutral-200 dark:bg-neutral-900 text-white font-sans flex flex-col min-h-screen">

    <!-- Navigation -->
    <nav class="bg-neutral-200 dark:bg-neutral-900 p-4 w-full fixed top-0 z-10">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex-shrink-0 animate__animated animate__fadeIn animate__delay-1s">
                <a href="#home">
                    <img src="resources/Logo.png" alt="Logo" class="w-24 h-24"> <!-- Increased logo size -->
                </a>
            </div>

            <!-- Centered Navigation Links -->
            <ul class="flex-1 flex justify-center space-x-8 text-blue-500">
                <li><a href="#home" class="hover:text-gray-300 animate__animated animate__fadeIn animate__delay-2s">Home</a></li>
                <li><a href="#services" class="hover:text-gray-300 animate__animated animate__fadeIn animate__delay-2s">Services</a></li>
                <li><a href="#about" class="hover:text-gray-300 animate__animated animate__fadeIn animate__delay-2s">About</a></li>
                <li><a href="#contact" class="hover:text-gray-300 animate__animated animate__fadeIn animate__delay-2s">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto p-6 text-center flex-grow pt-[10%] md:pt-[15%]">
        <div class="grid gap-4">
            <div class="flex items-center justify-center text-2xl md:text-4xl dark:text-white animate__animated animate__zoomIn">
                <span class="mr-2 page_wave-animation__eZFdU" style="animation-delay:1800ms">💸</span>
                <span class="page_special-text__JDcjy text-center">PREISE</span>
            </div>
            <p class="text-xl mt-4 animate__animated animate__fadeIn animate__delay-1s">
                Die Firma OmniCloud ist ein innovativer neuer Player im IaaS-Cloud-
                <br>Hyperscaler-Markt. Bestellen einer solchen VM müssen folgende
                <br>Angaben gemacht werden.
            </p>
        </div>

        <div class="mt-8 p-6 bg-neutral-300 dark:bg-neutral-800 rounded-lg shadow-lg animate__animated animate__fadeIn animate__delay-2s">
            <form id="vmForm" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
                <div class="flex-1">
                    <label for="cpu" class="block text-lg font-medium">CPU Cores:</label>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        1 Core (5 CHF) <br>
                        2 Cores (10 CHF) <br>
                        4 Cores (18 CHF) <br>
                        8 Cores (30 CHF) <br>
                        16 Cores (45 CHF) <br>
                    </div>
                    <select name="cpu" id="cpu" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md animate__animated animate__fadeIn">
                        <option value="1">1 Core</option>
                        <option value="2">2 Cores</option>
                        <option value="4">4 Cores</option>
                        <option value="8">8 Cores</option>
                        <option value="16">16 Cores</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label for="ram" class="block text-lg font-medium">RAM (MB):</label>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        512 MB (5 CHF) <br>
                        1,024 MB (10 CHF) <br>
                        2,048 MB (20 CHF) <br>
                        4,096 MB (40 CHF) <br>
                        8,192 MB (80 CHF) <br>
                        16,384 MB (160 CHF) <br>
                        32,768 MB (320 CHF)
                    </div>
                    <select name="ram" id="ram" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md animate__animated animate__fadeIn animate__delay-1s">
                        <option value="512">512 MB</option>
                        <option value="1024">1,024 MB</option>
                        <option value="2048">2,048 MB</option>
                        <option value="4096">4,096 MB</option>
                        <option value="8192">8,192 MB</option>
                        <option value="16384">16,384 MB</option>
                        <option value="32768">32,768 MB</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label for="ssd" class="block text-lg font-medium">SSD Storage (GB):</label>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        10 GB (5 CHF) <br>
                        20 GB (10 CHF) <br>
                        40 GB (20 CHF) <br>
                        80 GB (40 CHF) <br>
                        240 GB (120 CHF) <br>
                        500 GB (250 CHF) <br>
                        1000 GB (500 CHF)
                    </div>
                    <select name="ssd" id="ssd" class="w-full p-2 bg-neutral-200 dark:bg-neutral-900 rounded-md animate__animated animate__fadeIn animate__delay-2s">
                        <option value="10">10 GB</option>
                        <option value="20">20 GB</option>
                        <option value="40">40 GB</option>
                        <option value="80">80 GB</option>
                        <option value="240">240 GB</option>
                        <option value="500">500 GB</option>
                        <option value="1000">1000 GB</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 mt-4 animate__animated animate__fadeIn animate__delay-3s">Provision VM</button>
            </form>
        </div>

        <div id="resultMessage" class="mt-4"></div>
    </main>

    <script>
        $(document).ready(function() {
            $('#vmForm').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#resultMessage').html('<p>' + response.message + '</p>');
                    },
                    error: function(xhr, status, error) {
                        $('#resultMessage').html('<p>An error occurred: ' + error + '</p>');
                    }
                });
            });
        });
    </script>
</body>
</html>