<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface DataTransformerInterface
{
    public function toDTO(object $model, string $mapType = 'dto', array $options = []): array;
    public function toDtoCollection(iterable $models, string $mapName = 'dto', array $options = []): array;

    public function toModel(string $model, array $dto, string $mapType = 'dto'): object;
    public function toModelsCollection(string $model, iterable $dtoCollection, string $mapType = 'dto'): array;

    public function fillModel(object $model, array $data, string $mapType = 'dto'): object;
}
