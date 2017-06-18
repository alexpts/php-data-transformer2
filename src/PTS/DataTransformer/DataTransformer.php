<?php

namespace PTS\DataTransformer;

use PTS\Hydrator\HydratorService;

class DataTransformer implements DataTransformerInterface
{
    /** @var HydratorService */
    protected $hydratorService;
    /** @var MapsManager */
    protected $mapsManager;

    public function __construct(HydratorService $hydratorService, MapsManager $mapsManager)
    {
        $this->hydratorService = $hydratorService;
        $this->mapsManager = $mapsManager;
    }

    public function toModel(array $dto, string $class, string $mapName = 'dto')
    {
        $rules = $this->mapsManager->getMap($class, $mapName);
        $dto = $this->resolveRefHydrate($dto, $rules);
        return $this->hydratorService->hydrate($dto, $class, $rules);
    }

    public function fillModel(array $dto, $model, string $mapName = 'dto'): void
    {
        $rules = $this->mapsManager->getMap(get_class($model), $mapName);
        $dto = $this->resolveRefHydrate($dto, $rules);
        $this->hydratorService->hydrateModel($dto, $model, $rules);
    }

    protected function resolveRefHydrate(array $dto, array $rules): array
    {
        foreach ($dto as $key => $value) {
            $rule = $rules[$key];

            if ($value !== null && array_key_exists('ref', $rule)) {
                $refRules = $this->mapsManager->getMap($rule['ref']['model'], $rule['ref']['map']);
                $dto[$key] = $this->hydrateRefValue($refRules, $value, $rule);
            }
        }

        return $dto;
    }

    /**
     * @param array $refRules
     * @param array $childDTO
     * @param array $rule
     *
     * @return object|object[]
     */
    protected function hydrateRefValue(array $refRules, array $childDTO, array $rule)
    {
        if (array_key_exists('collection', $rule['ref']) && $rule['ref']['collection']) {
            $refModels = array_map(function($item) use ($refRules, $rule) {
                return $this->hydratorService->hydrate($item, $rule['ref']['model'], $refRules);
            }, $childDTO);

            return $refModels;
        }

        $refModel = $this->hydratorService->hydrate($childDTO, $rule['ref']['model'], $refRules);
        return $refModel;
    }

    public function toDTO($model, string $mapName = 'dto', array $excludeFields = []): array
    {
        $rules = $this->mapsManager->getMap(get_class($model), $mapName);

        foreach ($excludeFields as $field) {
            unset($rules[$field]);
        }

        $dto = $this->hydratorService->extract($model, $rules);
        return $this->resolveRefExtract($dto, $rules);
    }

    protected function resolveRefExtract(array $dto, array $rules): array
    {
        foreach ($dto as $key => $value) {
            $rule = $rules[$key];
            if ($value !== null && array_key_exists('ref', $rule)) {
                $refRules = $this->mapsManager->getMap($rule['ref']['model'], $rule['ref']['map']);
                $refDTO = $this->extractRefValue($refRules, $value, $rule);
                $dto[$key] = $refDTO;
            }
        }

        return $dto;
    }

    protected function extractRefValue(array $refRules, $value, array $rule): array
    {
        if (array_key_exists('collection', $rule['ref']) && $rule['ref']['collection'] === true) {
            $refDTO = array_map(function($item) use ($refRules) {
                return $this->hydratorService->extract($item, $refRules);
            }, $value);
        } else {
            $refDTO = $this->hydratorService->extract($value, $refRules);
        }

        return $refDTO;
    }

    public function getMapsManager(): MapsManager
    {
        return $this->mapsManager;
    }
}
