<?php
require_once(__DIR__ . '/../../../bootstrap.php');

use App\Models\BershkaQueue;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$goutteClient = new Client();
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('bershka', false, true, false, false);

echo " [*] Waiting for urls. To exit press CTRL+C\n";
$callback = function ($data) use ($goutteClient) {
    echo ' [x] Received ', $data->body, "\n";
    $urlObject = json_decode($data->body, true);
    $productPage = $goutteClient->request('GET', $urlObject['url']);
    if ($productPage) {
        $productPage->filter('.image-item')->each(function (Crawler $node, $i) {
            $imageUrl = $node->attr('data-original');
            if ($imageUrl) {
                $localProductImageName = __DIR__ . '/images/' . getImageAttribute($imageUrl) . '_' . $i . '.jpg';
                if (!file_exists($localProductImageName)) {
                    $file = file_put_contents($localProductImageName, file_get_contents($imageUrl));
                    if ($file) {
                        echo ' [x] Saved ', $localProductImageName, "\n";
                    } else {
                        echo ' [x] Saved FAILED ', $localProductImageName, "\n";
                    }
                }
            }
        });
        BershkaQueue::find($urlObject['id'])->update([
            'is_parsed' => 1
        ]);
    }
    echo " [x] Done\n";
    $data->delivery_info['channel']->basic_ack($data->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('bershka', '', false, false, false, false, $callback);

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