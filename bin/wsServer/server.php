#!/usr/bin/php
<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
require(__DIR__ . '/../../vendor/autoload.php');

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ws\socket\Socket;
$config = [
    'host' => 'localhost',
    'port' => 8888
];

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Socket()
        )
    ), $config['port']);
echo "Server listening on port: {$config['port']}..." . PHP_EOL;
$server->run();