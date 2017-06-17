<?php
namespace PTS\DataTransformer;

class MapsManager
{
    /** @var array */
    protected $cache = [];
    /** @var array */
    protected $mapsDirs = [];

    public function setMapDir(string $entityName, string $dir): void
    {
        $this->mapsDirs[$entityName] = $dir;
    }

    public function getMap(string $entityName, string $mapName = 'dto'): array
    {
        $map = $this->tryCache($entityName, $mapName);
        if (is_array($map)) {
            return $map;
        }

        $dir = $this->mapsDirs[$entityName];
        $map = $this->getByPath($dir . '/' . $mapName . '.php');

        $this->setCache($entityName, $mapName, $map);

        return $map;
    }

    protected function setCache(string $entityName, string $mapName, array $map): void
    {
        $this->cache[$entityName][$mapName] = $map;
    }

    protected function tryCache(string $entityName, string $mapName): ?array
    {
        if (isset($this->cache[$entityName], $this->cache[$entityName][$mapName])) {
            return $this->cache[$entityName][$mapName];
        }

        return null;
    }

    protected function getByPath(string $path): array
    {
        return require $path;
    }
}
