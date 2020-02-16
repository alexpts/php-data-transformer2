<?php

use Blackfire\Client;
use Blackfire\Profile\Configuration;
use Faker\Factory as Faker;
use PTS\DataTransformer\DataTransformer;

require_once __DIR__  .'/../vendor/autoload.php';
require_once 'UserModel.php';
require_once 'ChildModel.php';
$faker = Faker::create();

$iterations = $argv[1] ?? 1000;
$blackfire = $argv[2] ?? false;
$iterations++;

$service = new DataTransformer;
$service->getMapsManager()->setMapDir(UserModel::class, __DIR__ . '/maps/' . UserModel::class);
$service->getMapsManager()->setMapDir(ChildModel::class, __DIR__ . '/maps/' . ChildModel::class);

$collectionDto = [];
while ($iterations--) {
    $collectionDto[] = [
        'id' => $faker->randomDigit,
        'creAt' => $faker->unixTime(),
        'name' => $faker->name,
        'login' => $faker->name,
        'active' => $faker->numberBetween(0, 2),
        'email' => $faker->email,
        'child' => [
            'id' => $faker->randomDigit,
            'creAt' => time(),
            'name' => $faker->unixTime(),
            'login' => $faker->name,
            'active' => $faker->boolean,
            'email' => $faker->email,
        ]
    ];
}

if ($blackfire) {
    $client = new Client;
    $probe = $client->createProbe(new Configuration);
}
$startTime = microtime(true);

foreach ($collectionDto as $dto) {
    $model = $service->toModel(UserModel::class, $dto);
    $dto = $service->toDTO($model);
}

$diff = (microtime(true) - $startTime) * 1000;
echo sprintf('%2.3f ms', $diff);
echo "\n" . memory_get_peak_usage()/1024;

if ($blackfire) {
    $client->endProbe($probe);
}
