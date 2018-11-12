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
        $map = $this->manager->getMap('model.user', 'dto');
        self::assertCount(6, $map);
    }

    public function testGetMapWithCache(): void
    {
        $this->manager->setMapDir('model.user', __DIR__ . '/data');
        $map = $this->manager->getMap('model.user', 'dto');
        $map2 = $this->manager->getMap('model.user', 'dto');
        self::assertCount(6, $map2);
        self::assertEquals($map, $map2);
    }
}