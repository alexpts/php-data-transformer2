<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface DataTransformerInterface
{
    public function toDTO(object $model, string $mapName = 'dto', array $options = []): array;

    public function toDtoCollection(iterable $models, string $mapName = 'dto', array $options = []): array;

    public function toModel(string $class, array $dto, string $mapName = 'dto'): object;

    public function toModelsCollection(string $model, iterable $dtoCollection, string $mapType = 'dto'): array;

    public function fillModel(object $model, array $dto, string $mapName = 'dto'): object;
}
