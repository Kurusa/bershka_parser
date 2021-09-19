<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

require_once(__DIR__ . '/../../bootstrap.php');

$goutteClient = new Client();
$productPage = $goutteClient->request('GET', 'https://www.bershka.com/es/en/lace-up-boots-with-track-soles-c0p102437041.html');

var_dump($insertData);
