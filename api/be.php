<?php

class MotdBEInfo {
    public $status = "offline"; // 服务器状态 (online/offline)
    public $host; // 服务器地址
    public $motd; // Motd信息
    public $agreement; // 协议版本
    public $version; // 游戏版本
    public $online; // 在线人数
    public $max; // 最大在线人数
    public $levelName; // 存档名字
    public $gameMode; // 游戏模式
    public $serverUniqueID; // 服务器唯一ID
    public $delay; // 延迟时间
    public $error; // 错误信息
}

/**
* 通过UDP请求获取MotdBE信息
* @param string $host 服务器地址，格式为 host:port
* @param int $timeout 超时时间（秒）
* @return MotdBEInfo
*/
function MotdBE($host, $timeout = 5) {
    $errorReturn = new MotdBEInfo();
    $errorReturn->error = "Unknown error";

    if (empty($host)) {
        $errorReturn->error = "Host is empty";
        return $errorReturn;
    }

    // 解析主机地址和端口
    $hostInfo = explode(":", $host);
    if (count($hostInfo) != 2) {
        $errorReturn->error = "Invalid host format";
        return $errorReturn;
    }
    list($host, $port) = $hostInfo;

    // 创建UDP套接字
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!$socket) {
        $errorReturn->error = "Failed to create socket: " . socket_strerror(socket_last_error());
        return $errorReturn;
    }

    // 设置超时时间
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

    // 构建发送数据
    $packetID = chr(0x01); // Packet ID
    $clientSendTime = pack("J", time() * 1000); // 客户端发送时间（毫秒）
    $magic = "\x00\xFF\xFF\x00\xFE\xFE\xFE\xFE\xFD\xFD\xFD\xFD"; // Magic Number
    $clientID = "\x12\x34\x56\x78\x00"; // 客户端ID
    $clientGUID = pack("J", 0); // 客户端GUID

    $sendData = $packetID . $clientSendTime . $magic . $clientID . $clientGUID;

    // 发送数据
    $startTime = microtime(true) * 1000; // 发送时间（毫秒）
    if (!socket_sendto($socket, $sendData, strlen($sendData), 0, $host, $port)) {
        $errorReturn->error = "Failed to send data: " . socket_strerror(socket_last_error($socket));
        socket_close($socket);
        return $errorReturn;
    }

    // 接收数据
    $response = socket_read($socket, 256);
    socket_close($socket);

    $endTime = microtime(true) * 1000; // 接收时间（毫秒）

    if (!$response) {
        $errorReturn->error = "No response from server";
        return $errorReturn;
    }

    // 解析响应数据
    $serverInfo = substr($response, 33);
    $motdData = explode(";", $serverInfo);

    if (count($motdData) < 9) {
        $errorReturn->error = "Invalid response format";
        return $errorReturn;
    }

    $motd1 = $motdData[1];
    $protocolVersion = intval($motdData[2]);
    $versionName = $motdData[3];
    $playerCount = intval($motdData[4]);
    $maxPlayerCount = intval($motdData[5]);
    $serverUniqueID = $motdData[6];
    $motd2 = $motdData[7];
    $gameMode = $motdData[8];

    // 构造返回对象
    $motdInfo = new MotdBEInfo();
    $motdInfo->status = "online";
    $motdInfo->host = $host;
    $motdInfo->motd = $motd1;
    $motdInfo->agreement = $protocolVersion;
    $motdInfo->version = $versionName;
    $motdInfo->online = $playerCount;
    $motdInfo->max = $maxPlayerCount;
    $motdInfo->levelName = $motd2;
    $motdInfo->gameMode = $gameMode;
    $motdInfo->serverUniqueID = $serverUniqueID;
    $motdInfo->delay = $endTime - $startTime;

    return $motdInfo;
}
function isOnline($str) {
    if ($str == 'online') {
        return true;
    } return false;
}
function isDomain($string) {
    // 检查是否是IPv4地址
    if (filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    // 检查是否是IPv6地址
    if (filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return false;
    }

    // 检查是否是域名
    if (filter_var($string, FILTER_VALIDATE_DOMAIN)) {
        return true;
    }

    return false;
}
function getIp($dn) {
    $a = isDomain($dn) ? gethostbyname($dn) : $dn;
    if ($a == null) return $dn;
    return $a;
}


// 判断请求类型并获取主机地址
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 如果是 POST 请求，优先使用 $_POST 的值
    $host = isset($_POST['ip']) && trim($_POST['ip']) !== "" ? trim($_POST['ip']) : false;
} else {
    // 如果是 GET 请求，使用 $_GET 的值
    $host = isset($_GET['ip']) && trim($_GET['ip']) !== "" ? trim($_GET['ip']) : false;
}
// 检查主机地址是否有效
if ($host !== false) {
    // 解析主机地址和端口
    $hostInfo = explode(":", $host);
    if (count($hostInfo) == 1) {
        // 如果没有端口，自动补上默认端口 19132
        $host .= ":19132";
    } elseif (count($hostInfo) == 2) {
        // 如果有端口，检查端口是否有效
        list($host, $port) = $hostInfo;
        if (!is_numeric($port) || (int)$port > 65535) {
            $host = false; // 端口超出范围或无效
        } else {
            $host = $host . ":" . $port; // 重新组合主机地址
        }
    } else {
        $host = false; // 格式无效
    }
}

header('Content-Type: application/json');
if ($host == false) {
    $data = [
        "status" => 400,
        "online" => false,
        "message" => 'IP或端口输入无效'
    ];
    $json_data = json_encode($data);
    exit ($json_data);
}
$d = MotdBE($host);
if (isOnline($d->status)) {
    $rez = [
        'status' => 200,
        'online' => isOnline($d->status),
        'host' => $d->host,
        'ip' => getIp($d->host),
        'motd' => $d->motd,
        'agreement' => $d->agreement,
        'version' => $d->version,
        'onlines' => $d->online,
        'max' => $d->max,
        'level' => $d->levelName,
        'gamemode' => $d->gameMode,
        'id' => $d->serverUniqueID,
        'delay' => $d->delay,
    ];
} else {
    $rez = [
        'status' => 204,
        'online' => isOnline($d->status),
        'message' => '服务器离线'
    ];
}
$json_data = json_encode($rez);
exit ($json_data);

/**
* ["gameMode"]=> string(8) "Survival" ["serverUniqueID"]=> string(18) "275128025514481402" ["delay"]=> float(54.958740234375) ["error"]=> NULL }
*/
?>