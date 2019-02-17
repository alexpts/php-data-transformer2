<?php

namespace PTS\DataTransformer;

use PTS\Hydrator\HydratorService;

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
        $dto = $this->resolveRefPopulate($dto, $map['refs']);
        $dto = $this->applyPipes($dto, $map['pipe']);
        return $this->hydratorService->hydrate($dto, $class, $map['rules']);
    }

    public function toModelsCollection(string $class, array $dtoCollection, string $mapName = 'dto'): array
    {
        $map = $this->mapsManager->getMap($class, $mapName);

        $models = [];
        foreach ($dtoCollection as $dto) {
            $dto = $this->resolveRefPopulate($dto, $map['refs']);
            $dto = $this->applyPipes($dto, $map['pipe']);
            $models[] = $this->hydratorService->hydrate($dto, $class, $map['rules']);
        }

        return $models;
    }

    public function fillModel(object $model, array $dto, string $mapName = 'dto'): object
    {
        $map = $this->mapsManager->getMap(\get_class($model), $mapName);
        $dto = $this->resolveRefPopulate($dto, $map['refs']);
        $dto = $this->applyPipes($dto, $map['pipe']);
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
        $map = $this->mapsManager->getMap(\get_class($model), $mapName);
        $excludeRules = $options['excludeFields'] ?? [];

        foreach ($excludeRules as $name) {
            unset($map['pipe'][$name], $map['rules'][$name], $map['refs'][$name]);
        }

        $dto = $this->hydratorService->extract($model, $map['rules']);
        $dto = $this->applyPipes($dto, $map['pipe'], self::FILTER_TYPE_EXTRACT);
        return $this->resolveRefExtract($dto, $map['refs']);
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

    protected function applyPipes(array $dto, array $pipes, $type = self::FILTER_TYPE_POPULATE): array
    {
        $fieldsPipes = array_intersect_key($pipes, $dto);
        foreach ($fieldsPipes as $name => $filters) {
            $value = $dto[$name] ?? null;
            $dto[$name] = $this->applyFilters($value, $filters, $type);
        }

        return $dto;
    }

    protected function applyFilters($value, array $filters, $type)
    {
        foreach ($filters as $filter) {
            if (\is_callable($filter)) {
                $value = $filter($value);
                continue;
            }

            $value = ($filter[$type] ?? false) ? $filter[$type]($value) : $value;
        }

        return $value;
    }
}
