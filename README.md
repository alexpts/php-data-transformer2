# php-data-transformer2

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/de0407d9-12fe-4d3d-a688-9b29b10a0e46/big.png)](https://insight.sensiolabs.com/projects/de0407d9-12fe-4d3d-a688-9b29b10a0e46)

[![Build Status](https://travis-ci.org/alexpts/php-data-transformer2.svg?branch=master)](https://travis-ci.org/alexpts/php-data-transformer2)
[![Code Coverage](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)
[![Code Climate](https://codeclimate.com/github/alexpts/php-data-transformer2/badges/gpa.svg)](https://codeclimate.com/github/alexpts/php-data-transformer2)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)


### Install
`composer require alexpts/php-data-transformer2`

Библиотека представляет собой более высокий уровень билбиотеки https://github.com/alexpts/php-hydrator.
Расширяя ее возможности и упрощая работу за счет:
- Декларативного описания правил преобразования
- Рекурсивного преобразования вложенных моделей и коллекций моделей
- Более лаконичного синтаксиса

Базовые правила схем трансформации подробно описаны в проекте https://github.com/alexpts/php-hydrator.

### Data Transformer
Класс DataTransformer является более высокогоуровневым. Он позволяет работать с HydratorService и описывать схемы преобразования для каждого класса отдельно.

Для одного класса может быть множество схем преобразования. Например для преобразования модели для сохранения в БД требуется преобразовать ее в DTO сущность.
При этом все значения типа \DateTime преобразовать в timestamp. Но если мы передаем эту же модель на клиент через REST API, то схема преобразования может быть иной.
Все значения \DateTime нужно представить в виде строки в формате ISO8601.

```php
$dataTransformer = new DataTransformer;
$dataTransformer->getMapsManager()->setMapDir(UserModel::class, __DIR__ . '/data');

$model = $dataTransformer->toModel(UserModel::class, [
    'id' => 1,
    'creAt' => new \DateTime,
    'name' => 'Alex',
    'active' => 1,
]);

$dto = $dataTransformer->toDTO($model, 'dto');
$dtoForDb = $dataTransformer->toDTO($model, 'db');
```

А еще у нас может быть просто более компактное представлеиние этой же модели, без лишних деталей.
```php
$shortFormatDto = $dataTransformer->toDTO($model, 'short.dto');
```

### Коллекция моделей
Небольшой сахар, чтобы перевести коллекцию однотипных моделей в коллекцию DTO:
```php
$mapName = 'dto';
$excludedFields = ['name'];
$dtoCollection = $dataTransformer->toDtoCollection($models, $mapName, [
	'excludeFields' => $excludedFields
]);
```

### Вложенные модели
Если свойство модели представлено другой моделью или коллекцией моделей, то можно рекурсивно извлеч/заполнить модель.
Для этого в схеме маппинга нужно использовать ключ `ref`.

```php
// map file deepDto.php
return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'login' => [],
    'active' => [
        'pipe' => ['boolval']
    ],
    'email' => [
    	 'pipe' => [
    	 	[
    	 		'populate' => 'strtolower', // any callable
    	 		'extract' => 'strtoupper'
    	 	]
    	 ]
    ],
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

// code file
$model = $dataTransformer->toModel(UserModel::class, [
    'id' => 1,
    'creAt' => new \DateTime,
    'name' => 'Alex',
    'active' => 1,
    'refModel' => [
        'id' => 2,
        'name' => 'refModel',
    ]
], 'deepDto');

$model2 = $dataTransformer->toModel(UserModel::class, [
    'id' => 1,
    'creAt' => new \DateTime,
    'name' => 'Alex',
    'active' => 1,
    'refModels' => [ // collection ref models
        [
            'id' => 2,
            'name' => 'refModel',
        ],
        [
            'id' => 2,
            'name' => 'refModel',
        ]
    ]
], 'deepDto');
```


### Логика в pipe обработчиках
Обработчики pipe позволяют описывать callable методы и писать любую логику, которая будет применяться к значению.
В pipe фильтрах можно кастить типы например. Либо шифровать поля перед записью в БД.
В случае необходимости, чтобы вся логика маппинга была в 1 месте, вы может прокинуть любые зависимости через замыкание
в функцию pipe, доставл ее из контейнера.

```php
<?php
/**
 * @var MapsManager $this
 */

use PTS\DataTransformer\MapsManager;

$encrypter = $this->getContainer()->get('encrypter');

return [
    'id' => [],
    'creAt' => [],
    'name' => [],
    'password' => [
    	 'pipe' => [
    	 	[
    	 		'extract' => function(string $openPassword) use($encrypter) {
					return $encrypter->encrypt($openPassword, false);
				},
				'populate' => static function(string $ePassword) use($encrypter) {
					return $encrypter->decrypt($ePassword, false);
				},
			]
		]
	],

];