<?php
/**
 * @var MapsManager $this
 */

use PTS\DataTransformer\MapsManager;

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