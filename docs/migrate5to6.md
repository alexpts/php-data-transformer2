# Migrate 5 to 6

Change pipe format in map:
```php
// 5
$mapOld = [
    'active' => [
        'pipe' => [
            [
                'populate' => 'boolval',
                'extract' => 'boolval',
            ]
        ]
    ],
    'email' => [
        'pipe-populate' => ['strtolower'],
        'pipe-extract' => ['strtolower'],
        'pipe' => [
            'strtolower'
        ]
    ],
];

// 6
$mapNew = [
    'active' => [
        'pipe-populate' => [
            'boolval',
        ],
        'pipe-extract' => [
             'boolval',
        ]
    ],
    'email' => [
        'pipe-populate' => ['strtolower'],
        'pipe-extract' => ['strtolower'],
    ],
];

```