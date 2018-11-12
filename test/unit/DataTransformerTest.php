<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\DataTransformer\DataTransformer;
use PTS\DataTransformer\MapsManager;
use PTS\DataTransformer\UserModel;

require_once __DIR__ . '/data/UserModel.php';

class DataTransformerTest extends TestCase
{
    /** @var DataTransformer */
    protected $dataTransformer;
    /** @var \Faker\Generator */
    protected $faker;

    public function setUp(): void
    {
        $this->dataTransformer = new DataTransformer;
	    $this->dataTransformer->getMapsManager()->setMapDir(UserModel::class, __DIR__ . '/data');

        $this->faker = \Faker\Factory::create();
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

    public function testConstructor(): void
    {
        $this->assertInstanceOf(DataTransformer::class, $this->dataTransformer);
    }

    public function testGetMapsManager(): void
    {
    	$mapsManager = $this->dataTransformer->getMapsManager();
        $this->assertInstanceOf(MapsManager::class, $mapsManager);
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

	/**
	 * @throws Exception
	 */
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

	/**
	 * @throws Exception
	 */
	public function testToDtoCollection(): void
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

    public function testToModelsCollection(): void
    {
        $dtoCollection = [
            [
                'id' => 1,
                'creAt' => new \DateTime,
                'name' => 'Alex',
                'active' => true,
            ],
            [
                'id' => 2,
                'creAt' => new \DateTime,
                'name' => 'Bob',
                'active' => false,
            ]
        ];

        /** @var UserModel[] $models */
        $models = $this->dataTransformer->toModelsCollection($dtoCollection, UserModel::class);
        $this->assertCount(2, $models);

        $this->assertInstanceOf(UserModel::class, $models[0]);
        $this->assertEquals(1, $models[0]->getId());
        $this->assertEquals('Alex', $models[0]->getName());
        $this->assertEquals(true, $models[0]->isActive());

        $this->assertInstanceOf(UserModel::class, $models[1]);
        $this->assertEquals(2, $models[1]->getId());
        $this->assertEquals('Bob', $models[1]->getName());
        $this->assertEquals(false, $models[1]->isActive());
    }
}
