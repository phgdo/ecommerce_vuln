<?php
header('Content-Type: application/json');

// Function lấy % CPU
function getCpuUsage()
{
    $load = sys_getloadavg();
    return round($load[0] * 100 / max(1, shell_exec("nproc")), 2);
}

// Function lấy % RAM
function getRamUsage()
{
    $meminfo = file_get_contents("/proc/meminfo");
    preg_match("/MemTotal:\s+(\d+)/", $meminfo, $matches);
    $memTotal = $matches[1];
    preg_match("/MemAvailable:\s+(\d+)/", $meminfo, $matches);
    $memAvailable = $matches[1];
    return round((1 - $memAvailable / $memTotal) * 100, 2);
}

// Function lấy % Disk
function getDiskUsage()
{
    return round((disk_total_space("/") - disk_free_space("/")) / disk_total_space("/") * 100, 2);
}

// Function lấy tốc độ network (KB/s)
function getNetworkUsage()
{
    $net1 = file_get_contents("/proc/net/dev");
    usleep(500000); // 0.5s
    $net2 = file_get_contents("/proc/net/dev");

    preg_match_all('/\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $net1, $matches1);
    preg_match_all('/\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $net2, $matches2);

    $rx1 = array_sum($matches1[1]);
    $tx1 = array_sum($matches1[2]);
    $rx2 = array_sum($matches2[1]);
    $tx2 = array_sum($matches2[2]);

    return [
        "rx" => round(($rx2 - $rx1) / 1024 / 0.5, 2),
        "tx" => round(($tx2 - $tx1) / 1024 / 0.5, 2)
    ];
}

// Function kiểm tra kết nối MySQL
function checkMysqlConnection($host, $user, $pass, $db)
{
    $mysqli = @new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_errno) {
        return false;
    }
    $mysqli->close();
    return true;
}

// Config MySQL
$mysqlStatus = checkMysqlConnection("127.0.0.1", "root", "password", "ecom");

$network = getNetworkUsage();

echo json_encode([
    "cpu" => getCpuUsage(),
    "ram" => getRamUsage(),
    "disk" => getDiskUsage(),
    "network_rx" => $network["rx"],
    "network_tx" => $network["tx"],
    "mysql_status" => $mysqlStatus
]);
