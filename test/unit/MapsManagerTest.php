<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\MapsManager;

require_once __DIR__ . '/data/UserModel.php';
require_once __DIR__ . '/data/Container.php';

class MapsManagerTest extends TestCase
{
    protected MapsManager $manager;

    public function setUp(): void
    {
        $this->manager = new MapsManager;
    }

    public function testSetMapDir(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $result = $this->manager->getMap('model.user');
        static::assertNotNull($result);
    }

    public function testGetMap(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $map = $this->manager->getMap('model.user');
        static::assertCount(3, $map);
        static::assertCount(6, $map['rules']);
        static::assertCount(2, $map['pipe']);
        static::assertCount(0, $map['refs']);
    }

    public function testGetMapWithCache(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $map = $this->manager->getMap('model.user');
        $map2 = $this->manager->getMap('model.user');
        static::assertCount(3, $map2);
        static::assertCount(6, $map2['rules']);
        static::assertCount(2, $map2['pipe']);
        static::assertCount(0, $map2['refs']);
        static::assertSame($map, $map2);
    }

    public function testGetMapFromDefaultDir(): void
    {
        $this->manager->setDefaultMapDir(__DIR__ . '/data');
        $map = $this->manager->getMap('Namespace\UserModel');
        static::assertCount(3, $map);
        static::assertCount(6, $map['rules']);
        static::assertCount(2, $map['pipe']);
        static::assertCount(0, $map['refs']);
    }

    public function testContainer(): void
    {
        $container = new Container;
        $this->manager->setContainer($container);
        $value = $this->manager->getContainer()->get('any');
        static::assertSame(1, $value);
    }
}
