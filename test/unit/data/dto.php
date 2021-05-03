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
        'pipe-populate' => ['boolval'],
        'pipe-extract' => ['boolval'],
    ],
    'email' => [
        'pipe-populate' => ['strtolower'],
        'pipe-extract' => ['strtolower'],
    ],
];