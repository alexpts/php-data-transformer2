# php-data-transformer2

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/de0407d9-12fe-4d3d-a688-9b29b10a0e46/big.png)](https://insight.sensiolabs.com/projects/de0407d9-12fe-4d3d-a688-9b29b10a0e46)

[![Build Status](https://travis-ci.org/alexpts/php-data-transformer2.svg?branch=master)](https://travis-ci.org/alexpts/php-data-transformer2)
[![Code Coverage](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)
[![Code Climate](https://codeclimate.com/github/alexpts/php-data-transformer2/badges/gpa.svg)](https://codeclimate.com/github/alexpts/php-data-transformer2)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alexpts/php-data-transformer2/?branch=master)

Библиотека представляет собой более высокий уровень билбиотеки https://github.com/alexpts/php-hydrator.
Расширяя ее возможности и упрощая работу за счет:
- Декларативного описания правил преобразования
- Рекурсивного преобразования вложенных моделей и коллекций моделей
- Более лаконичного синтаксису

Базовые правила схем трансформации подробно описаны в проекте https://github.com/alexpts/php-hydrator.

### Data Transformer
Класс DataTransformer является еще более высокого уровнемвым. Он позволяет работать с HydratorService и описывать схемы преобразования для каждого класса отдельно.

Для одного класса может быть множество схем преобразования. Например для преобразования модели для сохранения в БД требуется преобразовать ее в DTO сущность.
При этом все значения типа \DateTime преобразовать в timestamp. 

Но если мы передаем эту же модель на клиент через REST API, то схема преобразования может быть иной.
Все значения \DateTime нужно представить в виде строки в формате ISO8601.

```php
$normalizeRule = new NormalizerRule;
$extractor = new Extractor(new ExtractClosure, $normalizeRule);
$hydrator = new Hydrator(new HydrateClosure, $normalizeRule);
$hydratorService = new HydratorService($extractor, $hydrator);

$mapsManager = new MapsManager;
$mapsManager->setMapDir(UserModel::class, __DIR__ . '/data');

$dataTransformer = new DataTransformer($hydratorService, $mapsManager);

$model = $dataTransformer->toModel([
    'id' => 1,
    'creAt' => new \DateTime,
    'name' => 'Alex',
    'active' => 1,
], UserModel::class);

$dto = $dataTransformer->toDTO($model);
```

А еще у нас может быть просто более компактное представлеиние этой же модели, без лишних деталей.
```php
$shortFormatDto = $dataTransformer->toDTO($model, 'short.dto');
```

### Коллекция моделей
Небольшой сахар, чтобы перевести коллекцию однотипных моделей в коллекцию DTO:
```php
$mapName = 'dto';
$excludedFields = [];
$dtoCollection = $dataTransformer->toDtoCollection($models, $mapName, $excludedFields);
```

### Вложенные модели
Если свойство модели представлено другой моделью или коллекцией моделей, то можно рекурсивно извреч/заполнить модель.
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

// code file
$model = $dataTransformer->toModel([
    'id' => 1,
    'creAt' => new \DateTime,
    'name' => 'Alex',
    'active' => 1,
    'refModel' => [
        'id' => 2,
        'name' => 'refModel',
    ]
], UserModel::class, 'deepDto');

$model2 = $dataTransformer->toModel([
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
], UserModel::class, 'deepDto');
```
