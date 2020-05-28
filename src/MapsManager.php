<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

use Psr\Container\ContainerInterface;

class MapsManager
{
    protected array $cache = [];
    /** @var string[] */
    protected array $mapsDirs = [];
    protected NormalizerInterface $normalizer;
    protected string $defaultMapsDirs = '';
    protected ?ContainerInterface $container = null;

    public function __construct(NormalizerInterface $normalizer = null, string $dir = '')
    {
        $this->normalizer = $normalizer ?? new Normalizer;
        $this->setDefaultMapDir($dir);
    }

    public function setContainer(ContainerInterface $container): self
    {
    	$this->container = $container;
    	return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
    	return $this->container;
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
	        $rules = $this->includeMap($dir.DIRECTORY_SEPARATOR.$mapName.'.php');
            $this->setMap($rules, $entityName, $mapName);
        }

        return $this->cache[$entityName][$mapName];
    }

    protected function includeMap(string $file): array
    {
	    return require $file;
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
