<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface DataTransformerInterface
{
    public function toDTO(object $model, string $mapType = 'dto', array $excludeFields = []): array;
    public function toDtoCollection(array $models, string $mapName = 'dto', array $excludeFields = []): array;

    public function toModel(array $dto, string $model, string $mapType = 'dto');
    public function toModelsCollection(array $dtoCollection, string $model, string $mapType = 'dto'): array;

    public function fillModel(array $data, object $model, string $mapType = 'dto');
}
