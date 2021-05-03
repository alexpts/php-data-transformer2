<?php

use PTS\DataTransformer\UserModel;

return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'login' => [],
    'active' => [
        'pipe-populate' => ['boolval'],
        'pipe-extract' => ['boolval'],
    ],
    'email' => [],
    'refModel' => [
        'ref' => [
            'model' => UserModel::class,
            'map' => 'dto'
        ]
    ],
    'refModels' => [
        'ref' => [
            'model' => UserModel::class,
            'map' => 'dto',
            'collection' => true
        ]
    ],
];