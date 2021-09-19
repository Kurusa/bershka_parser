<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('bershka_products_n', false, true, false, false);

echo " [*] Waiting for urls. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(1);
    echo " [x] Done\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('bershka_products_n', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();