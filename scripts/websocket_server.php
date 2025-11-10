<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:2346');
$clients = [];

$worker->onConnect = function($conn) use (&$clients) {
    $clients[$conn->id] = $conn;
    echo "✅ Frontend Connected: ".count($clients)."\n";
};

$worker->onMessage = function($conn, $data) use (&$clients) {
    $decoded = json_decode($data, true);
    if (!$decoded) return;

    if ($decoded['type'] === 'new_email') {
        foreach ($clients as $c) $c->send(json_encode($decoded));
    }
};

$worker->onClose = function($conn) use (&$clients) {
    unset($clients[$conn->id]);
    echo "🔌 Frontend Disconnected: ".count($clients)."\n";
};

echo "🚀 WebSocket Server Running on 2346...\n";
Worker::runAll();
