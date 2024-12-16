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
    private $used_cpu = 0;
    private $used_ram = 0;
    private $used_ssd = 0;
    public $vms = []; 

    public function __construct($name, $cpu, $ram, $ssd) {
        $this->name = $name;
        $this->total_cpu = $cpu;
        $this->total_ram = $ram;
        $this->total_ssd = $ssd;
    }

    public function canAllocate(VM $vm) {
        return $this->total_cpu - $this->used_cpu >= $vm->cpu &&
               $this->total_ram - $this->used_ram >= $vm->ram &&
               $this->total_ssd - $this->used_ssd >= $vm->ssd;
    }

    public function allocate(VM $vm) {
        if ($this->canAllocate($vm)) {
            $this->used_cpu += $vm->cpu;
            $this->used_ram += $vm->ram;
            $this->used_ssd += $vm->ssd;
            $this->vms[] = $vm; 
            $this->logVMChange("Added", $vm); 
            return true;
        }
        return false;
    }

    public function getAvailableResources() {
        $this->total_cpu = $this->total_cpu - $this->used_cpu;
        $this->total_ram = $this->total_ram - $this->used_ram;
        $this->total_ssd = $this->total_ssd - $this->used_ssd;

        return [
            'cpu' => $this->total_cpu,
            'ram' => $this->total_ram,
            'ssd' => $this->total_ssd,
        ];
    }


    public function getTotalRevenue() {
        
        return $this->used_cpu * 5 + $this->used_ram * 0.1 + $this->used_ssd * 0.05;
    }

    private function logVMChange($action, VM $vm) {
        $logMessage = "$action VM: {$vm->name}, CPU: {$vm->cpu}, RAM: {$vm->ram}, SSD: {$vm->ssd}, Price: {$vm->price} CHF\n";
        file_put_contents('vm_changes.txt', $logMessage, FILE_APPEND);
    }
}

class OmniCloud {
    private $servers;

    public function __construct($servers) {
        $this->servers = $servers;
    }

    public function allocateVMToBestServer(VM $vm) {
        foreach ($this->servers as $server) {
            if ($server->allocate($vm)) {
                return $server->name;
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

    public function getServers() {
        return $this->servers;
    }

    public function deleteVM($vmName) {
        foreach ($this->servers as $server) {
            foreach ($server->vms as $index => $vm) {
                if ($vm->name === $vmName) {
                    $server->used_cpu -= $vm->cpu;
                    $server->used_ram -= $vm->ram;
                    $server->used_ssd -= $vm->ssd;
                    unset($server->vms[$index]);
                    $server->logVMChange("Removed", $vm);
                    return true;
                }
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
    header('Content-Type: application/json');
    $response = ['message' => ''];

    try {
        if (isset($_POST['delete_vm'])) {
            $vmName = $_POST['delete_vm'];
            if ($omniCloud->deleteVM($vmName)) {
                $response['message'] = "VM $vmName deleted successfully.";
            }
        } else if (isset($_POST['cpu']) && isset($_POST['ram']) && isset($_POST['ssd'])) {
            $cpu = intval($_POST['cpu']);
            $ram = intval($_POST['ram']);
            $ssd = intval($_POST['ssd']);
            $vmName = "VM-" . uniqid();

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

            $response['available_resources'] = [];
            foreach ($omniCloud->getServers() as $server) {
                $response['available_resources'][$server->name] = $server->getAvailableResources();
            }
        } else {
            $response['message'] = "Invalid input.";
        }
    } catch (Exception $e) {
        $response['message'] = "An error occurred: " . $e->getMessage();
    }
    echo json_encode($response);
    exit();
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
    document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('vmForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const cpu = parseInt(document.getElementById('cpu').value);
        const ram = parseInt(document.getElementById('ram').value);
        const ssd = parseInt(document.getElementById('ssd').value);

        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cpu=${cpu}&ram=${ram}&ssd=${ssd}`
        })
        .then(response => response.json())
        .then(data => {
            const resultMessageElement = document.getElementById('resultMessage');
            const totalRevenueElement = document.getElementById('total_revenue');
            const availableResourcesElement = document.getElementById('available_resources');

            resultMessageElement.innerText += data.message + '\n';
            if (data.total_revenue) {
                totalRevenueElement.innerText = `Total Revenue: ${data.total_revenue} CHF`;
            }
            if (data.available_resources) {
                let resourcesMessage = 'Available Resources:\n';
                for (const [server, resources] of Object.entries(data.available_resources)) {
                    resourcesMessage += `${server}: CPU: ${resources.cpu}, RAM: ${resources.ram}, SSD: ${resources.ssd}\n`;
                }
                availableResourcesElement.innerText = resourcesMessage;
            }

            
            const vmListElement = document.getElementById('vm_list');
            const newVmElement = document.createElement('div');
            const vmName = `VM-${Date.now()}`; 
            newVmElement.innerHTML = `VM: CPU: ${cpu}, RAM: ${ram}, SSD: ${ssd} <button class="delete-vm" data-vm-name="${vmName}">Delete</button>`;
            vmListElement.appendChild(newVmElement);

           
            newVmElement.querySelector('.delete-vm').addEventListener('click', function() {
                const vmName = this.getAttribute('data-vm-name');
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `delete_vm=${vmName}`
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('resultMessage').innerText += data.message + '\n';
                    vmListElement.removeChild(newVmElement);
                    
                    const totalRevenueElement = document.getElementById('total_revenue');
                    const availableResourcesElement = document.getElementById('available_resources');

                    
                    fetch('index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'get_resources=true'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.total_revenue) {
                            totalRevenueElement.innerText = `Total Revenue: ${data.total_revenue} CHF`;
                        }
                        if (data.available_resources) {
                            let resourcesMessage = 'Available Resources:\n';
                            for (const [server, resources] of Object.entries(data.available_resources)) {
                                resourcesMessage += `${server}: CPU: ${resources.cpu}, RAM: ${resources.ram}, SSD: ${resources.ssd}\n`;
                            }
                            availableResourcesElement.innerText = resourcesMessage;
                        }
                    })
                    .catch(error => {
                        document.getElementById('resultMessage').innerText += `An error occurred: ${error}\n`;
                    });
                })
                .catch(error => {
                    document.getElementById('resultMessage').innerText += `An error occurred: ${error}\n`;
                });
            });
        })
        .catch(error => {
            document.getElementById('resultMessage').innerText += `An error occurred: ${error}\n`;
        });
    });
});
</script>
</head>
<body class="bg-neutral-200 dark:bg-neutral-900 text-white font-sans flex flex-col min-h-screen">

    <!-- Navigation -->
    <nav class="bg-neutral-200 dark:bg-neutral-900 p-4 w-full fixed top-0 z-10">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex-shrink-0 animate__animated animate__fadeIn">
                <a href="index.php">
                    <img src="/resources/Logo.png" alt="OmniCloud Logo" class="w-24 h-24">
                </a>
            </div>

            <!-- Centered Navigation Links -->
            <ul class="absolute left-1/2 transform -translate-x-1/2 flex space-x-8 text-blue-500">
                <li><a href="" class="hover:text-gray-300">Home</a></li>
                <li><a href="/pages/services.html" class="hover:text-gray-300">Services</a></li>
                <li><a href="/pages/about.html" class="hover:text-gray-300">About</a></li>
                <li><a href="/pages/Contact.html" class="hover:text-gray-300">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto p-20 text-center flex-grow mt-20">
        <div class="grid gap-4">
            <div class="flex items-center justify-center text-2xl md:text-4xl dark:text-white animate__animated animate__zoomIn">
                <span class="mr-2 page_wave-animation__eZFdU" style="animation-delay:1800ms">💸</span>
                <h1 class="text-4xl md:text-5xl font-bold page_special-text__JDcjy">PRICE</h1>
            </div>
            <p class="text-xl mt-4 animate__animated animate__fadeIn animate__delay-1s">
                OmniCloud is an innovative new player in the IaaS cloud
                <br>hyperscaler market. To order such a VM, the following
                <br>details must be provided.
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
                        <br>
                        <br>

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
                    <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 mt-4 animate__animated animate__fadeIn animate__delay-3s">Provision VM</button>
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
                
            </form>
        </div>

        <div id="resultMessage" class="mt-4 animate__animated animate__fadeIn animate__delay-2s"></div>
        <div class="info-section mt-4 animate__animated animate__fadeIn animate__delay-3s">
            <h3>Total Revenue</h3>
            <p id="total_revenue"></p>
        </div>
        <div class="info-section mt-4 animate__animated animate__fadeIn animate__delay-4s">
            <h3>Available Resources</h3>
            <p id="available_resources"></p>
        </div>
        <div class="info-section mt-4 animate__animated animate__fadeIn animate__delay-5s">
            <h3>Virtual Machines List</h3>
            <p id="vm_list"></p>
        </div>

    </main>

    <script>
    </script>

    <!-- Footer -->
    <footer class="bg-neutral-200 dark:bg-neutral-900 text-gray-500 p-4 text-center">
        <p>&copy; 2024 OmniCloud. All rights reserved.</p>
    </footer>

</body>
</html>