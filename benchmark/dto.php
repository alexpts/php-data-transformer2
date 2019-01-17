<?php
declare(strict_types=1);

return [
    'id' => [],
    'creAt' => [],
    'name' => [
        'get' => 'getName',
        'set' => 'setName'
    ],
    'login' => [],
    'active' => [
        'pipe' => ['boolval']
    ],
    'email' => [
        'pipe' => [
            [
                'populate' => 'strtolower',
                'extract' => 'strtoupper'
            ]
        ]
    ],
    'childModel' => [
        'ref' => [
            'model' => 'UserModel',
            'map' => 'dto'
        ]
    ]
];
