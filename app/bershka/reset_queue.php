<?php
require_once(__DIR__ . '/../../bootstrap.php');

use App\Models\BershkaQueue;

$urlsToParse = BershkaQueue::update([
    'is_parsed' => 0
]);
