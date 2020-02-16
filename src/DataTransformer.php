<?php

namespace PTS\DataTransformer;

use PTS\Hydrator\HydratorService;
use function get_class;
use function is_callable;

class DataTransformer implements DataTransformerInterface
{
    protected const FILTER_TYPE_POPULATE = 'populate';
    protected const FILTER_TYPE_EXTRACT = 'extract';

    /** @var HydratorService */
    protected $hydratorService;
    /** @var MapsManager */
    protected $mapsManager;

    public function __construct(HydratorService $hydratorService = null, MapsManager $mapsManager = null)
    {
        $this->hydratorService = $hydratorService ?? new HydratorService;
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
        $dto = $map['pipe'] ? $this->applyPipes($dto, $map['pipe']) : $dto;
        return $this->hydratorService->hydrate($dto, $class, $map['rules']);
    }

    public function toModelsCollection(string $class, array $dtoCollection, string $mapName = 'dto'): array
    {
        $map = $this->mapsManager->getMap($class, $mapName);

        $models = [];
        foreach ($dtoCollection as $dto) {
            $dto = $map['refs'] ? $this->resolveRefPopulate($dto, $map['refs']) : $dto;
            $dto = $map['pipe'] ? $this->applyPipes($dto, $map['pipe']) : $dto;
            $models[] = $this->hydratorService->hydrate($dto, $class, $map['rules']);
        }

        return $models;
    }

    public function fillModel(object $model, array $dto, string $mapName = 'dto'): object
    {
        $map = $this->mapsManager->getMap(get_class($model), $mapName);
        $dto = $map['refs'] ? $this->resolveRefPopulate($dto, $map['refs']) : $dto;
        $dto = $map['pipe'] ? $this->applyPipes($dto, $map['pipe']) : $dto;
        $this->hydratorService->hydrateModel($dto, $model, $map['rules']);

        return $model;
    }

    public function toDtoCollection(array $models, string $mapName = 'dto', array $options = []): array
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
        $map = $this->mapsManager->getMap(get_class($model), $mapName);
        $excludeRules = $options['excludeFields'] ?? [];

        foreach ($excludeRules as $name) {
            unset($map['pipe'][$name], $map['rules'][$name], $map['refs'][$name]);
        }

        $dto = $this->hydratorService->extract($model, $map['rules']);
        $dto = $map['pipe'] ? $this->applyPipes($dto, $map['pipe'], self::FILTER_TYPE_EXTRACT) : $dto;
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

    /**
     * Рекурсиовно все refs модели создает
     *
     * @param array $dto
     * @param array $refsRules
     *
     * @return array
     */
    protected function resolveRefPopulate(array $dto, array $refsRules): array
    {
        foreach ($refsRules as $name => $rule) {
            $value = $dto[$name] ?? null;
            if ($value !== null) {
                $method = ($rule['collection'] ?? false) ? 'toModelsCollection' : 'toModel';
                $dto[$name] = $this->{$method}($rule['model'], $value, $rule['map']);
            }
        }

        return $dto;
    }

    protected function applyPipes(array $dto, array $pipes, string $type = self::FILTER_TYPE_POPULATE): array
    {
        $fieldsPipes = array_intersect_key($pipes, $dto);
        foreach ($fieldsPipes as $name => $filters) {
            $value = $dto[$name] ?? null;
            $dto[$name] = $this->applyFilters($value, $filters, $type);
        }

        return $dto;
    }

    protected function applyFilters($value, array $filters, string $type)
    {
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $value = $filter($value);
                continue;
            }

            $value = ($filter[$type] ?? false) ? $filter[$type]($value) : $value;
        }

        return $value;
    }
}
