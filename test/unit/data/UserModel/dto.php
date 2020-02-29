<?php

return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'login' => [],
    'active' => [
        'pipe' => [
            [
                'populate' => 'boolval',
                'extract' => 'boolval',
            ]
        ]
    ],
    'email' => [
        'pipe' => [
            'strtolower'
        ]
    ],
];