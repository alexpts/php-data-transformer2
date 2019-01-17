<?php

use Blackfire\Client;
use Blackfire\Profile\Configuration;
use PTS\DataTransformer\DataTransformer;

require_once __DIR__  .'/../vendor/autoload.php';
require_once 'UserModel.php';

$iterations = $argv[1] ?? 1000;
$blackfire = $argv[2] ?? false;
$iterations++;

if ($blackfire) {
    $client = new Client;
    $probe = $client->createProbe(new Configuration);
}

$startTime = microtime(true);
$service = new DataTransformer;
$service->getMapsManager()->setMapDir(UserModel::class, __DIR__);

$dto =  [
    'id' => 1,
    'creAt' => time(),
    'name' => 'Alex',
    'login' => 'login',
    'active' => 1,
    'email' => 'some@cloud.net',
    'childModel' => [
        'id' => 2,
        'creAt' => time(),
        'name' => 'Alex2',
        'login' => 'login2',
        'active' => false,
        'email' => 'some2@cloud.net',
    ]
];

while ($iterations--) {
    $model = $service->toModel(UserModel::class, $dto);
    $dto = $service->toDTO($model);
}

$diff = (microtime(true) - $startTime) * 1000;
echo sprintf('%2.3f ms', $diff);
echo "\n" . memory_get_peak_usage()/1024;

if ($blackfire) {
    $client->endProbe($probe);
}
