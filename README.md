# php-data-transformer2

[![Build Status](https://travis-ci.org/alexpts/php-data-transformer2.svg?branch=master)](https://travis-ci.org/alexpts/php-data-transformer2)
[![Code Coverage](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)
[![Code Climate](https://codeclimate.com/github/alexpts/php-data-transformer2/badges/gpa.svg)](https://codeclimate.com/github/alexpts/php-data-transformer2)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)

Позволяет извлекать данные из объектов и создавать объекты из данных. Позволяет делать это по заранее опрелделенной схеме в обе стороны. Например извлечь данные из Model для записи в БД. Либо создать/заполнить Model данными из БД.


### Install

`composer require alexpts/php-data-transformer2`

Библиотека представляет собой более высокий уровень билбиотеки https://github.com/alexpts/php-hydrator. Расширяя ее
возможности и упрощая работу за счет:

- Декларативного описания правил преобразования
- Рекурсивного преобразования вложенных моделей и коллекций моделей
- Более лаконичного синтаксиса

Базовые правила схем трансформации подробно описаны в проекте https://github.com/alexpts/php-hydrator.

### Data Transformer

Класс DataTransformer является более высокоуровневым. Он позволяет работать с HydratorService и описывать схемы
преобразования для каждого класса отдельно.

Для одного класса может быть множество схем преобразования. Для преобразования модели для сохранения в БД
требуется преобразовать ее в DTO сущность (массив php). При этом все значения типа \DateTime преобразовать в timestamp (integer тип). Если мы передаем эту же модель на клиент через REST API, то схема преобразования может быть иной. Все значения \DateTime нужно представить в виде строки в формате ISO8601.

```php
use PTS\DataTransformer\DataTransformer;

$dataTransformer = new DataTransformer;
$dataTransformer->getMapsManager()->setMapDir(UserModel::class, __DIR__ . '/data');

$model = $dataTransformer->toModel(UserModel::class, [
    'id' => 1,
    'creAt' => new DateTime,
    'name' => 'Alex',
    'active' => 1,
]);

$dto = $dataTransformer->toDTO($model, 'dto');
$dtoForDb = $dataTransformer->toDTO($model, 'db');
```

### Вариации представлений

Может потребоваться для разных сценариев извлекать данные по разным правиоам из модели.
Либо может быть просто более компактное представлеиние этой же модели, без лишних деталей. Можно использовать несколько схем для 1 модели, например `short.dto`:

```php
$shortFormatDto = $dataTransformer->toDTO($model, 'short.dto');
```

Также можно исключить часть полей, без необъодимости определять новую схему/map для преобразования, указав при вызовы опцию `excludeFields` с массивом игнорируемых полей в схеме:

```php
$shortFormatDto = $dataTransformer->toDTO($model, 'dto', [
     'excludeFields' => ['password']
]);
```

### Коллекция моделей

Небольшой сахар, чтобы перевести коллекцию однотипных моделей в коллекцию DTO:

```php
$mapName = 'dto';
$excludedFields = ['name'];
$dtoCollection = $dataTransformer->toDtoCollection($models, $mapName);
```

### Вложенные модели

Если свойство модели представлено другой моделью или коллекцией моделей, то можно рекурсивно извлечь/заполнить модель. Для этого в схеме маппинга нужно использовать ключ `ref`.

```php
// map file deepDto.php
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
        'pipe-populate' => [ // any callable
            'strval',
            'strtolower',
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
    'creAt' => new DateTime,
    'name' => 'Alex',
    'active' => 1,
    'refModel' => [
        'id' => 2,
        'name' => 'refModel',
    ]
], 'deepDto');

$model2 = $dataTransformer->toModel(UserModel::class, [
    'id' => 1,
    'creAt' => new DateTime,
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

Обработчики pipe позволяют описывать callable методы и писать любую логику, которая будет применяться к значению. В pipe обработчиках можно кастить типы. Либо шифровать поля перед записью в БД. В случае необходимости, чтобы вся логика маппинга была в 1 месте, вы может прокинуть любые зависимости через замыкание в функцию pipe, достав их из контейнера `$this->getContainer()`.

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
        'pipe-populate' => [
            'strtolower',
            function(string $openPassword) use($encrypter) {
                return $encrypter->encrypt($openPassword);
            },
        ],
        'pipe-extract' => [
            function(string $ePassword) use($encrypter) {
                return $encrypter->decrypt($ePassword);
            },
            'strtolower'
        ],
    ]
];
```

### migration

[update 5 to 6](https://github.com/alexpts/php-data-transformer2/blob/master/docs/migrate5to6.md)
