<?php
declare(strict_types=1);

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\DataTransformer;
use PTS\DataTransformer\MapsManager;
use PTS\DataTransformer\UserModel;

require_once __DIR__ . '/data/UserModel.php';

class DataTransformerTest extends TestCase
{
    protected DataTransformer $dataTransformer;
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        $this->dataTransformer = new DataTransformer;
        $this->dataTransformer->getMapsManager()->setMapDir(UserModel::class, __DIR__ . '/data');

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
        $user->setEmail(strtoupper($this->faker->email));
        $user->setLogin($this->faker->name);
        $user->setName($this->faker->name);

        return $user;
    }

    public function testConstructor(): void
    {
        static::assertInstanceOf(DataTransformer::class, $this->dataTransformer);
    }

    public function testGetMapsManager(): void
    {
        $mapsManager = $this->dataTransformer->getMapsManager();
        static::assertInstanceOf(MapsManager::class, $mapsManager);
    }

    /**
     * @throws Exception
     */
    public function testToModel(): void
    {
        $model = $this->dataTransformer->toModel(UserModel::class, [
            'id' => 1,
            'creAt' => new DateTime,
            'name' => 'Alex',
            'active' => 1,
        ]);

        static::assertInstanceOf(UserModel::class, $model);
        static::assertEquals(true, $model->isActive());
        static::assertEquals(1, $model->getId());
        static::assertEquals('Alex', $model->getName());
    }

    /**
     * @throws Exception
     */
    public function testFillModel(): void
    {
        $model = new UserModel;
        $model = $this->dataTransformer->fillModel($model, [
            'id' => 1,
            'creAt' => new DateTime,
            'name' => 'Alex',
            'active' => 1,
        ]);

        static::assertInstanceOf(UserModel::class, $model);
        static::assertEquals(true, $model->isActive());
        static::assertEquals(1, $model->getId());
        static::assertEquals('Alex', $model->getName());
    }

    /**
     * @throws Exception
     */
    public function testToDTO(): void
    {
        $model = $this->createUser();
        $dto = $this->dataTransformer->toDTO($model);
        static::assertEquals([
            'id' => $model->getId(),
            'creAt' => $model->getCreAt(),
            'name' => $model->getName(),
            'login' => $model->getLogin(),
            'active' => $model->isActive(),
            'email' => strtolower($model->getEmail()),
        ], $dto);
    }

    public function testToDTOWithExcludeFields(): void
    {
        $model = new UserModel;
        $model->setId(1);
        $model->setActive(false);

        $dto = $this->dataTransformer->toDTO($model, 'dto', [
            'excludeFields' => ['email', 'login'],
        ]);
        static::assertEquals([
            'id' => 1,
            'creAt' => $model->getCreAt(),
            'name' => null,
            'active' => false,
        ], $dto);
    }

    /**
     * @throws Exception
     */
    public function testToDtoCollection(): void
    {
        $model1 = $this->createUser();
        $model2 = $this->createUser();
        $models = [$model1, $model2];

        $dtoCollection = $this->dataTransformer->toDtoCollection($models);
        static::assertCount(2, $dtoCollection);

        static::assertSame([
            'id' => $model1->getId(),
            'creAt' => $model1->getCreAt(),
            'name' => $model1->getName(),
            'login' => $model1->getLogin(),
            'active' => $model1->isActive(),
            'email' => strtolower($model1->getEmail()),
        ], $dtoCollection[0]);

        static::assertSame([
            'id' => $model2->getId(),
            'creAt' => $model2->getCreAt(),
            'name' => $model2->getName(),
            'login' => $model2->getLogin(),
            'active' => $model2->isActive(),
            'email' => strtolower($model2->getEmail()),
        ], $dtoCollection[1]);
    }

    /**
     * @throws Exception
     */
    public function testToModelsCollection(): void
    {
        $dtoCollection = [
            [
                'id' => 1,
                'creAt' => new DateTime,
                'name' => 'Alex',
                'active' => true,
            ],
            [
                'id' => 2,
                'creAt' => new DateTime,
                'name' => 'Bob',
                'active' => false,
            ],
        ];

        /** @var UserModel[] $models */
        $models = $this->dataTransformer->toModelsCollection(UserModel::class, $dtoCollection);
        static::assertCount(2, $models);

        static::assertInstanceOf(UserModel::class, $models[0]);
        static::assertEquals(1, $models[0]->getId());
        static::assertEquals('Alex', $models[0]->getName());
        static::assertEquals(true, $models[0]->isActive());

        static::assertInstanceOf(UserModel::class, $models[1]);
        static::assertEquals(2, $models[1]->getId());
        static::assertEquals('Bob', $models[1]->getName());
        static::assertEquals(false, $models[1]->isActive());
    }
}
