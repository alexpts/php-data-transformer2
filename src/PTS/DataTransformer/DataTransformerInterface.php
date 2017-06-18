<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface DataTransformerInterface
{
    public function toDTO($model, string $mapType = 'dto', array $excludeFields = []): array;

    public function toModel(array $data, string $model, string $mapType = 'dto');

    public function fillModel(array $data, $model, string $mapType = 'dto');
}
