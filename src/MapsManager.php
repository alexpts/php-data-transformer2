<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

class MapsManager
{
    /** @var array */
    protected $cache = [];
    /** @var string[] */
    protected $mapsDirs = [];
    /** @var NormalizerInterface */
    protected $normalizer;
    /** @var string */
    protected $defaultMapsDirs = '';

    public function __construct(NormalizerInterface $normalizer = null, string $dir = '')
    {
        $this->normalizer = $normalizer ?? new Normalizer;
        $this->setDefaultMapDir($dir);
    }

    public function setDefaultMapDir(string $dir): void
    {
        $this->defaultMapsDirs = $dir;
    }

    public function setMapDir(string $entityName, string $dir): void
    {
        $this->mapsDirs[$entityName] = $dir;
    }

    public function getMap(string $entityName, string $mapName = 'dto'): array
    {
        $map = $this->cache[$entityName][$mapName] ?? null;
        if ($map === null) {
            $dir = $this->getMapDir($entityName);
            $rules = require $dir.DIRECTORY_SEPARATOR.$mapName.'.php';
            $this->setMap($rules, $entityName, $mapName);
        }

        return $this->cache[$entityName][$mapName];
    }

    public function getMapDir(string $entityName): string
    {
        $dir = $this->mapsDirs[$entityName] ?? null;

        if (!$dir) {
            $parts = explode('\\', $entityName);
            $class = array_pop($parts);
            $dir = $this->defaultMapsDirs . DIRECTORY_SEPARATOR . $class;
        }

        return $dir;
    }

    public function setMap(array $rules, string $entityName, $mapName = 'dto'): void
    {
        $this->cache[$entityName][$mapName] = $this->normalizer->normalize($rules);
    }
}
