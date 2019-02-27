<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

class MapsManager
{
    /** @var array */
    protected $cache = [];
    /** @var array */
    protected $mapsDirs = [];
    /** @var NormalizerInterface */
    protected $normalizer;

    public function __construct(NormalizerInterface $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new Normalizer;
    }

    public function setMapDir(string $entityName, string $dir): void
    {
        $this->mapsDirs[$entityName] = $dir;
    }

    public function getMap(string $entityName, string $mapName = 'dto'): array
    {
        $map = $this->cache[$entityName][$mapName] ?? null;
        if ($map === null) {
            $dir = $this->mapsDirs[$entityName];
            $rules = require $dir.'/'.$mapName.'.php';
            $this->setMap($rules, $entityName, $mapName);
        }

        return $this->cache[$entityName][$mapName];
    }

    public function setMap(array $rules, string $entityName, $mapName = 'dto'): void
    {
        $this->cache[$entityName][$mapName] = $this->normalizer->normalize($rules);
    }
}
