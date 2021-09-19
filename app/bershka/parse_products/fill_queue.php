<?php
require_once(__DIR__ . '/../../../bootstrap.php');

use App\Models\BershkaQueue;
use Goutte\Client;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$client = new \GuzzleHttp\Client();
$goutteClient = new Client();
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$res = $channel->queue_declare('bershka_product_urls', false, true, false, false);

$womenSitemapString = $client->get('https://www.bershka.com/sitemap/productos/sitemap_productos_ru-ru_women.xml.gz');
$womenSitemapXml = simplexml_load_string($womenSitemapString->getBody());
foreach ($womenSitemapXml as $url) {
    BershkaQueue::updateOrCreate([
        'url' => strval($url->loc)
    ]);
}
$menSitemapString = $client->get('https://www.bershka.com/sitemap/productos/sitemap_productos_ru-ru_men.xml.gz');
$menSitemapXml = simplexml_load_string($menSitemapString->getBody());
foreach ($menSitemapXml as $url) {
    BershkaQueue::updateOrCreate([
        'url' => strval($url->loc)
    ]);
}

$urlsToParse = BershkaQueue::where('is_parsed', 0)->get();
foreach ($urlsToParse as $urlObject) {
    $msg = new AMQPMessage(json_encode([
        'id' => $urlObject->id,
        'url' => $urlObject->url,
    ]),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    );

    $channel->basic_publish($msg, '', 'bershka_product_urls');
    echo ' [x] Sent ', $urlObject->url, "\n";
}