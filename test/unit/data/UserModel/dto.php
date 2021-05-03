<?php

return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'login' => [],
    'active' => [
//        'pipe' => [
//            [
//                'populate' => 'boolval',
//                'extract' => 'boolval',
//            ],
//        ],
        'pipe-populate' => [
            'boolval'
        ],
        'pipe-extract' => [
            'boolval'
        ]
    ],
    'email' => [
//        'pipe' => [
//            'strtolower',
//        ],
        'pipe-populate' => [
            'strtolower'
        ],
        'pipe-extract' => [
            'strtolower'
        ]
    ],
];