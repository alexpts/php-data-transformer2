<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\DataTransformer;
use PTS\DataTransformer\MapsManager;
use PTS\DataTransformer\UserModel;
use PTS\Hydrator\ExtractClosure;
use PTS\Hydrator\Extractor;
use PTS\Hydrator\HydrateClosure;
use PTS\Hydrator\Hydrator;
use PTS\Hydrator\HydratorService;
use PTS\Hydrator\NormalizerRule;

require_once __DIR__ . '/data/UserModel.php';

class DataTransformerTest extends TestCase
{
    /** @var DataTransformer */
    protected $dataTransformer;
    /** @var \Faker\Generator */
    protected $faker;

    public function setUp(): void
    {
        $normalizeRule = new NormalizerRule;
        $extractor = new Extractor(new ExtractClosure, $normalizeRule);
        $hydrator = new Hydrator(new HydrateClosure, $normalizeRule);
        $hydratorService = new HydratorService($extractor, $hydrator);

        $mapsManager = new MapsManager;
        $mapsManager->setMapDir(UserModel::class, __DIR__ . '/data');

        $this->dataTransformer = new DataTransformer($hydratorService, $mapsManager);

        $this->faker = \Faker\Factory::create();
    }

    protected function createUser(): UserModel
    {
        $user = new UserModel;
        $user->setId(random_int(1, 9999));
        $user->setActive($this->faker->randomElement([true, false]));
        $user->setEmail($this->faker->email);
        $user->setLogin($this->faker->name);
        $user->setName($this->faker->name);

        return $user;
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(DataTransformer::class, $this->dataTransformer);
    }

    public function testGetMapsManager(): void
    {
        $this->assertInstanceOf(MapsManager::class, $this->dataTransformer->getMapsManager());
    }

    public function testToModel(): void
    {
        /** @var UserModel $model */
        $model = $this->dataTransformer->toModel([
            'id' => 1,
            'creAt' => new \DateTime,
            'name' => 'Alex',
            'active' => 1,
        ], UserModel::class);

        $this->assertInstanceOf(UserModel::class, $model);
        $this->assertEquals(true, $model->isActive());
        $this->assertEquals(1, $model->getId());
        $this->assertEquals('Alex', $model->getName());
    }

    public function testFillModel(): void
    {
        $model = new UserModel;
        $this->dataTransformer->fillModel([
            'id' => 1,
            'creAt' => new \DateTime,
            'name' => 'Alex',
            'active' => 1,
        ], $model);

        $this->assertInstanceOf(UserModel::class, $model);
        $this->assertEquals(true, $model->isActive());
        $this->assertEquals(1, $model->getId());
        $this->assertEquals('Alex', $model->getName());
    }

    public function testFillModelRefModel(): void
    {
        $model = new UserModel;
        $this->dataTransformer->fillModel([
            'id' => 1,
            'name' => 'Alex',
            'refModel' => [
                'id' => 2,
                'creAt' => new \DateTime,
                'name' => 'Alex2',
                'active' => true,
            ]
        ], $model, 'withRefs.map');

        $this->assertInstanceOf(UserModel::class, $model);
        $this->assertInstanceOf(UserModel::class, $model->refModel);
        $this->assertEquals(2, $model->refModel->getId());
    }

    public function testToModelRefModel(): void
    {
        /** @var UserModel $model */
        $model = $this->dataTransformer->toModel([
            'id' => 1,
            'name' => 'Alex',
            'refModel' => [
                'id' => 2,
                'creAt' => new \DateTime,
                'name' => 'Alex2',
                'active' => true,
            ]
        ], UserModel::class, 'withRefs.map');

        $this->assertInstanceOf(UserModel::class, $model);
        $this->assertInstanceOf(UserModel::class, $model->refModel);
        $this->assertEquals(2, $model->refModel->getId());
    }

    public function testToModelRefModels(): void
    {
        /** @var UserModel $model */
        $model = $this->dataTransformer->toModel([
            'id' => 1,
            'name' => 'Alex',
            'refModels' => [
                [
                    'id' => 2,
                    'name' => 'Alex2',
                ],
                [
                    'id' => 3,
                    'name' => 'Alex3',
                ]
            ]
        ], UserModel::class, 'withRefs.map');

        $this->assertCount(2, $model->refModels);
        $this->assertInstanceOf(UserModel::class, $model->refModels[0]);
        $this->assertInstanceOf(UserModel::class, $model->refModels[1]);
        $this->assertEquals(2, $model->refModels[0]->getId());
        $this->assertEquals(3, $model->refModels[1]->getId());
    }

    public function testToDTO(): void
    {
        $model = $this->createUser();
        $dto = $this->dataTransformer->toDTO($model);
        $this->assertEquals([
            'id' => $model->getId(),
            'creAt' => $model->getCreAt(),
            'name' => $model->getName(),
            'login' => $model->getLogin(),
            'active' => $model->isActive(),
            'email' => $model->getEmail(),
        ], $dto);
    }

    public function testToDTOWithExcludeFields(): void
    {
        $model = new UserModel;
        $model->setId(1);
        $model->setActive(false);

        $dto = $this->dataTransformer->toDTO($model, 'dto', ['email', 'login']);
        $this->assertEquals([
            'id' => 1,
            'creAt' => $model->getCreAt(),
            'name' => null,
            'active' => false,
        ], $dto);
    }

    public function testToDtoRefModel()
    {
        $model = $this->createUser();
        $model2= $this->createUser();

        $model->refModel = $model2;

        $dto = $this->dataTransformer->toDTO($model, 'withRefs.map', ['refModels']);
        $this->assertEquals($dto['refModel'], [
            'id' => $model2->getId(),
            'creAt' => $model2->getCreAt(),
            'name' => $model2->getName(),
            'login' => $model2->getLogin(),
            'active' => $model2->isActive(),
            'email' => $model2->getEmail(),
        ]);
    }

    public function testToDtoRefModels()
    {
        $model = $this->createUser();
        $model2= $this->createUser();
        $model3= $this->createUser();

        $model->refModels = [$model2, $model3];

        $dto = $this->dataTransformer->toDTO($model, 'withRefs.map', ['refModel']);
        $this->assertCount(2, $dto['refModels']);
        $this->assertEquals($dto['refModels'], [
            [
                'id' => $model2->getId(),
                'creAt' => $model2->getCreAt(),
                'name' => $model2->getName(),
                'login' => $model2->getLogin(),
                'active' => $model2->isActive(),
                'email' => $model2->getEmail(),
            ],
            [
                'id' => $model3->getId(),
                'creAt' => $model3->getCreAt(),
                'name' => $model3->getName(),
                'login' => $model3->getLogin(),
                'active' => $model3->isActive(),
                'email' => $model3->getEmail(),
            ]
        ]);
    }

    public function testToDtoCollection()
    {
        $model1 = $this->createUser();
        $model2 = $this->createUser();
        $models = [$model1, $model2];

        $dtoCollection = $this->dataTransformer->toDtoCollection($models);
        $this->assertCount(2, $dtoCollection);

        $this->assertEquals([
            'id' => $model1->getId(),
            'creAt' => $model1->getCreAt(),
            'name' => $model1->getName(),
            'login' => $model1->getLogin(),
            'active' => $model1->isActive(),
            'email' => $model1->getEmail(),
        ], $dtoCollection[0]);

        $this->assertEquals([
            'id' => $model2->getId(),
            'creAt' => $model2->getCreAt(),
            'name' => $model2->getName(),
            'login' => $model2->getLogin(),
            'active' => $model2->isActive(),
            'email' => $model2->getEmail(),
        ], $dtoCollection[1]);
    }

}