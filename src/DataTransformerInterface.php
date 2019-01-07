<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface DataTransformerInterface
{
    public function toDTO(object $model, string $mapType = 'dto', array $excludeFields = []): array;
    public function toDtoCollection(array $models, string $mapName = 'dto', array $excludeFields = []): array;

    public function toModel(string $model, array $dto, string $mapType = 'dto'): object;
    public function toModelsCollection(string $model, array $dtoCollection, string $mapType = 'dto'): array;

    public function fillModel(object $model, array $data, string $mapType = 'dto'): object;
}
