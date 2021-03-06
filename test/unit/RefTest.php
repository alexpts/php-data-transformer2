<?php
declare(strict_types=1);

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\DataTransformer;
use PTS\DataTransformer\MapsManager;
use PTS\DataTransformer\UserModel;
use PTS\Hydrator\ExtractClosure;
use PTS\Hydrator\Extractor;
use PTS\Hydrator\HydrateClosure;
use PTS\Hydrator\Hydrator;

require_once __DIR__ . '/data/UserModel.php';

class RefTest extends TestCase
{
    protected DataTransformer $dataTransformer;
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        $extractor = new Extractor(new ExtractClosure);
        $hydrator = new Hydrator(new HydrateClosure);

        $mapsManager = new MapsManager;
        $mapsManager->setMapDir(UserModel::class, __DIR__ . '/data');

        $this->dataTransformer = new DataTransformer($extractor, $hydrator, $mapsManager);

        $this->faker = Factory::create();
    }

    /**
     * @return UserModel
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testFillModelRefModel(): void
    {
        $model = new UserModel;
        $this->dataTransformer->fillModel($model, [
            'id' => 1,
            'name' => 'Alex',
            'refModel' => [
                'id' => 2,
                'creAt' => new DateTime,
                'name' => 'Alex2',
                'active' => true,
            ]
        ], 'withRefs.map');

        static::assertInstanceOf(UserModel::class, $model);
        static::assertInstanceOf(UserModel::class, $model->refModel);
        static::assertEquals(2, $model->refModel->getId());
    }

    /**
     * @throws Exception
     */
    public function testFillModelRefModelWithExtraFields(): void
    {
        $model = new UserModel;
        $this->dataTransformer->fillModel($model, [
            'id' => 1,
            'name' => 'Alex',
            'extraFieldWithoutDeclarateTransform' => 3,
            'refModel' => [
                'id' => 2,
                'creAt' => new DateTime,
                'name' => 'Alex2',
                'active' => true,
            ]
        ], 'withRefs.map');

        static::assertInstanceOf(UserModel::class, $model);
        static::assertInstanceOf(UserModel::class, $model->refModel);
        static::assertEquals(2, $model->refModel->getId());
    }

    /**
     * @throws Exception
     */
    public function testToModelRefModel(): void
    {
        /** @var UserModel $model */
        $model = $this->dataTransformer->toModel(UserModel::class, [
            'id' => 1,
            'name' => 'Alex',
            'refModel' => [
                'id' => 2,
                'creAt' => new DateTime,
                'name' => 'Alex2',
                'active' => true,
            ]
        ], 'withRefs.map');

        static::assertInstanceOf(UserModel::class, $model);
        static::assertInstanceOf(UserModel::class, $model->refModel);
        static::assertEquals(2, $model->refModel->getId());
    }

    public function testToModelRefModels(): void
    {
        /** @var UserModel $model */
        $model = $this->dataTransformer->toModel(UserModel::class, [
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
        ], 'withRefs.map');

        static::assertCount(2, $model->refModels);
        static::assertInstanceOf(UserModel::class, $model->refModels[0]);
        static::assertInstanceOf(UserModel::class, $model->refModels[1]);
        static::assertEquals(2, $model->refModels[0]->getId());
        static::assertEquals(3, $model->refModels[1]->getId());
    }

    /**
     * @throws Exception
     */
    public function testToDtoRefModel(): void
    {
        $model = $this->createUser();
        $model2= $this->createUser();

        $model->refModel = $model2;

        $dto = $this->dataTransformer->toDTO($model, 'withRefs.map', ['refModels']);
        static::assertEquals($dto['refModel'], [
            'id' => $model2->getId(),
            'creAt' => $model2->getCreAt(),
            'name' => $model2->getName(),
            'login' => $model2->getLogin(),
            'active' => $model2->isActive(),
            'email' => $model2->getEmail(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function testToDtoRefModels(): void
    {
        $model = $this->createUser();
        $model2= $this->createUser();
        $model3= $this->createUser();

        $model->refModels = [$model2, $model3];

        $dto = $this->dataTransformer->toDTO($model, 'withRefs.map', ['refModel']);
        static::assertCount(2, $dto['refModels']);
        static::assertEquals($dto['refModels'], [
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
}
