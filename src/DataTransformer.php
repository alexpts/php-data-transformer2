<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

use PTS\Hydrator\Extractor;
use PTS\Hydrator\ExtractorInterface;
use PTS\Hydrator\Hydrator;
use PTS\Hydrator\HydratorInterface;

class DataTransformer implements DataTransformerInterface
{
    protected ExtractorInterface $extractor;
    protected HydratorInterface $hydrator;
    protected MapsManager $mapsManager;

    public function __construct(
        ExtractorInterface $extractor = null,
        HydratorInterface $hydrator = null,
        MapsManager $mapsManager = null
    ) {
        $this->extractor = $extractor ?? new Extractor;
        $this->hydrator = $hydrator ?? new Hydrator;
        $this->mapsManager = $mapsManager ?? new MapsManager;
    }

    public function getMapsManager(): MapsManager
    {
        return $this->mapsManager;
    }

    public function toModel(string $class, array $dto, string $mapName = 'dto'): object
    {
        $map = $this->mapsManager->getMap($class, $mapName);
        $dto = $map['refs'] ? $this->resolveRefPopulate($dto, $map['refs']) : $dto;
        $dto = $map['pipe-populate'] ? $this->pipes($dto, $map['pipe-populate']) : $dto;
        return $this->hydrator->hydrate($dto, $class, $map['rules']);
    }

    public function toModelsCollection(string $model, iterable $dtoCollection, string $mapType = 'dto'): array
    {
        $models = [];
        foreach ($dtoCollection as $key => $dto) {
            $dto = $this->toModel($model, $dto, $mapType);
            $models[$key] = $dto;
        }

        return $models;
    }

    public function fillModel(object $model, array $dto, string $mapName = 'dto'): object
    {
        $map = $this->mapsManager->getMap($model::class, $mapName);
        $dto = $map['refs'] ? $this->resolveRefPopulate($dto, $map['refs']) : $dto;
        $dto = $map['pipe-populate'] ? $this->pipes($dto, $map['pipe-populate']) : $dto;
        $this->hydrator->hydrateModel($dto, $model, $map['rules']);

        return $model;
    }

    public function toDtoCollection(iterable $models, string $mapName = 'dto', array $options = []): array
    {
        $collection = [];
        foreach ($models as $key => $model) {
            $dto = $this->toDTO($model, $mapName, $options);
            $collection[$key] = $dto;
        }

        return $collection;
    }

    public function toDTO(object $model, string $mapName = 'dto', array $options = []): array
    {
        $map = $this->mapsManager->getMap($model::class, $mapName);
        $excludeRules = $options['excludeFields'] ?? [];

        foreach ($excludeRules as $name) {
            unset($map['pipe'][$name], $map['rules'][$name], $map['refs'][$name]);
        }

        $dto = $this->extractor->extract($model, $map['rules']);
        $dto = $map['pipe-extract'] ? $this->pipes($dto, $map['pipe-extract']) : $dto;
        return $map['refs'] ? $this->resolveRefExtract($dto, $map['refs']) : $dto;
    }

    protected function resolveRefExtract(array $dto, array $refsRules): array
    {
        foreach ($refsRules as $name => $rule) {
            $value = $dto[$name] ?? null;
            if ($value !== null) {
                $method = ($rule['collection'] ?? false) ? 'toDtoCollection' : 'toDTO';
                $dto[$name] = $this->{$method}($value, $rule['map']);
            }
        }

        return $dto;
    }

    protected function resolveRefPopulate(array $dto, array $refsRules): array
    {
        foreach ($refsRules as $name => $rule) {
            $value = $dto[$name] ?? null;
            if ($value !== null) {
                $dto[$name] = ($rule['collection'] ?? false)
                    ? $this->toModelsCollection($rule['model'], $value, $rule['map'])
                    : $this->toModel($rule['model'], $value, $rule['map']);
            }
        }

        return $dto;
    }

    /**
     * @param array $dto
     * @param callable[][] $pipes
     *
     * @return array - modified dto
     */
    protected function pipes(array $dto, array $pipes): array
    {
        $fieldsPipes = array_intersect_key($pipes, $dto);
        foreach ($fieldsPipes as $name => $filters) {
            $value = $dto[$name] ?? null;
            foreach ($filters as $filter) {
                $value = $filter($value);
            }

            $dto[$name] = $value;
        }

        return $dto;
    }
}
