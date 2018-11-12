<?php

namespace PTS\DataTransformer;

use PTS\Hydrator\HydratorService;

class DataTransformer implements DataTransformerInterface
{
    /** @var HydratorService */
    protected $hydratorService;
    /** @var MapsManager */
    protected $mapsManager;

    public function __construct(HydratorService $hydratorService = null, MapsManager $mapsManager = null)
    {
        $this->hydratorService = $hydratorService ?? new HydratorService;
        $this->mapsManager = $mapsManager ?? new MapsManager;
    }

    public function toModel(array $dto, string $class, string $mapName = 'dto')
    {
        $rules = $this->mapsManager->getMap($class, $mapName);
        $dto = $this->resolveRefHydrate($dto, $rules);
        return $this->hydratorService->hydrate($dto, $class, $rules);
    }

    public function toModelsCollection(array $dtoCollection, string $class, string $mapName = 'dto'): array
    {
        $rules = $this->mapsManager->getMap($class, $mapName);

        $models = [];
        foreach ($dtoCollection as $dto) {
            $dto = $this->resolveRefHydrate($dto, $rules);
            $models[] = $this->hydratorService->hydrate($dto, $class, $rules);
        }

        return $models;
    }

    public function fillModel(array $dto, object $model, string $mapName = 'dto'): void
    {
        $rules = $this->mapsManager->getMap(\get_class($model), $mapName);
        $dto = $this->resolveRefHydrate($dto, $rules);
        $this->hydratorService->hydrateModel($dto, $model, $rules);
    }

    protected function resolveRefHydrate(array $dto, array $rules): array
    {
        foreach ($dto as $key => $value) {
            if ($value !== null && $this->checkRuleForHydrate($rules, $key)) {
                $dto[$key] = $this->hydrateRefValue($this->getRefRules($rules[$key]), $value, $rules[$key]);
            }
        }

        return $dto;
    }

    protected function checkRuleForHydrate(array $rules, string $key): bool
    {
        return array_key_exists($key, $rules) && array_key_exists('ref', $rules[$key]);
    }

    protected function getRefRules(array $rule): array
    {
        return $this->mapsManager->getMap($rule['ref']['model'], $rule['ref']['map']);
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

    public function toDtoCollection(array $models, string $mapName = 'dto', array $excludeFields = []): array
    {
        $collection = [];
        foreach ($models as $key => $model) {
            $dto = $this->toDTO($model, $mapName, $excludeFields);
            $collection[$key] = $dto;
        }

        return $collection;
    }

    public function toDTO($model, string $mapName = 'dto', array $excludeFields = []): array
    {
        $rules = $this->mapsManager->getMap(\get_class($model), $mapName);

        foreach ($excludeFields as $field) {
            unset($rules[$field]);
        }

        $dto = $this->hydratorService->extract($model, $rules);
        return $this->resolveRefExtract($dto, $rules);
    }

    protected function resolveRefExtract(array $dto, array $rules): array
    {
        foreach ($dto as $key => $value) {
            if ($value !== null && $this->checkRuleForHydrate($rules, $key)) {
                $rule = $rules[$key];
                $refRules = $this->mapsManager->getMap($rule['ref']['model'], $rule['ref']['map']);
                $refDTO = $this->extractRefValue($refRules, $value, $rule);
                $dto[$key] = $refDTO;
            }
        }

        return $dto;
    }

	/**
	 * @param array $refRules
	 * @param object|object[] $value
	 * @param array $rule
	 *
	 * @return array
	 */
    protected function extractRefValue(array $refRules, $value, array $rule): array
    {
	     return (array_key_exists('collection', $rule['ref']) && $rule['ref']['collection'] === true)
		    ? $this->extractItems($value, $refRules)
			: $this->extractItem($value, $refRules);
    }

	protected function extractItems(array $models, array $refRules): array
	{
		return array_map(function(object $model) use ($refRules) {
			return $this->extractItem($model, $refRules);
		}, $models);
	}

    protected function extractItem(object $model, array $refRules): array
    {
	    return $this->hydratorService->extract($model, $refRules);
    }

    public function getMapsManager(): MapsManager
    {
        return $this->mapsManager;
    }
}
