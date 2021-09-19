<?php
require_once(__DIR__ . '/../../bootstrap.php');

use App\Models\BershkaQueue;
use Goutte\Client;
use PhpAmqpLib\Message\AMQPMessage;

$client = new \GuzzleHttp\Client();
$goutteClient = new Client();

$womenSitemapString = $client->get('https://www.bershka.com/sitemap/productos/sitemap_productos_ru-ru_women.xml.gz');
$womenSitemapXml = simplexml_load_string($womenSitemapString->getBody());
foreach ($womenSitemapXml as $url) {
    $urlObject = BershkaQueue::updateOrCreate([
        'url' => strval($url->loc)
    ]);
    new AMQPMessage(json_encode([
        'id' => $urlObject->id,
        'url' => $urlObject->url,
    ]),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
    );

}

$menSitemapString = $client->get('https://www.bershka.com/sitemap/productos/sitemap_productos_ru-ru_men.xml.gz');
$menSitemapXml = simplexml_load_string($menSitemapString->getBody());
foreach ($menSitemapXml as $url) {
    $urlObject = BershkaQueue::updateOrCreate([
        'url' => strval($url->loc)
    ]);
}
