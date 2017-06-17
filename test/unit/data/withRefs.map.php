<?php

use PTS\DataTransformer\UserModel;

return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'login' => [],
    'active' => [
        'pipe' => ['boolval']
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