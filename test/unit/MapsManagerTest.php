<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\MapsManager;

require_once __DIR__ . '/data/UserModel.php';

class MapsManagerTest extends TestCase
{
    /** @var MapsManager */
    protected $manager;

    public function setUp()
    {
        $this->manager = new MapsManager;
    }

    public function testSetMapDir(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $result = $this->manager->getMap('model.user');
        self::assertNotNull($result);
    }

    public function testGetMap(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $map = $this->manager->getMap('model.user');
        self::assertCount(3, $map);
        self::assertCount(6, $map['rules']);
        self::assertCount(2, $map['pipe']);
        self::assertCount(0, $map['refs']);
    }

    public function testGetMapWithCache(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $map = $this->manager->getMap('model.user');
        $map2 = $this->manager->getMap('model.user');
        self::assertCount(3, $map2);
        self::assertCount(6, $map2['rules']);
        self::assertCount(2, $map2['pipe']);
        self::assertCount(0, $map2['refs']);
        self::assertSame($map, $map2);
    }
}
