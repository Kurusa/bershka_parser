<?php
require_once __DIR__ . '/../../../bootstrap.php';

use App\Models\BershkaQueue;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('bershka', false, true, false, false);

$urlsToParse = BershkaQueue::where('is_parsed', 0)->get();
foreach ($urlsToParse as $urlObject) {
    $msg = new AMQPMessage(json_encode([
            'id' => $urlObject->id,
            'url' => $urlObject->url,
        ]),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    );

    $channel->basic_publish($msg, '', 'bershka');
    echo ' [x] Sent ', $urlObject->url, "\n";
}

$channel->close();
$connection->close();