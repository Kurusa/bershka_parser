<?php
require_once(__DIR__ . '/../../../bootstrap.php');

use App\Models\BershkaProducts;
use App\Models\BershkaQueue;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$goutteClient = new Client();
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('bershka_product_urls', false, true, false, false);

echo " [*] Waiting for urls. To exit press CTRL+C\n";

$callback = function ($data) use ($goutteClient) {
    echo ' [x] Received ', $data->body, "\n";
    $urlObject = json_decode($data->body, true);
    $productPage = $goutteClient->request('GET', $urlObject['url']);
    if ($productPage) {
        $insertData = [];
        $insertData['bershka_queue_id'] = $urlObject['id'];
        if ($productPage->filter('.product-reference')->count()) {
            $insertData['reference'] = explode(' ', $productPage->filter('.product-reference')->html())[1];
        } else {
            echo " [x] Ref not found. Skip\n";
            $data->delivery_info['channel']->basic_ack($data->delivery_info['delivery_tag']);
            exit();    
	}
        $insertData['title'] = $productPage->filter('.product-title')->html();
        $insertData['price'] = $productPage->filter('.current-price-elem')->html();

        $insertData['lining'] = '';
        $productPage->filter('.composition')->each(function (Crawler $node, $i) use (&$insertData) {
            $insertData['lining'] .= $node->html();
        });
        $insertData['description'] = '';
        $productPage->filter('.description')->each(function (Crawler $node, $i) use (&$insertData) {
            $insertData['description'] .= $node->html();
        });

        BershkaProducts::create($insertData);
        BershkaQueue::find($urlObject['id'])->update([
            'is_parsed' => 1
        ]);
    }
    echo " [x] Done\n";
    $data->delivery_info['channel']->basic_ack($data->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('bershka_product_urls', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();


function getImageAttribute(string $imageUrl): string
{
    // get string like 8040168800_1_1_3.jpg?t=1594280812666
    $explodedImageUrl = explode('/', $imageUrl);
    $newExploded = explode('_', end($explodedImageUrl));
    return $newExploded[0];
}
