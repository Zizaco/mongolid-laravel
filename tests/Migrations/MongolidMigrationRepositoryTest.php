<?php
namespace MongolidLaravel\Migrations;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use MongolidLaravel\TestCase;
use SplFixedArray;
use stdClass;

class MongolidMigrationRepositoryTest extends TestCase
{
    public function testGetRanMigrationsListMigrationsByPackage()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'sort' => ['batch' => 1, 'migration' => 1],
                    'projection' => ['_id' => 0, 'migration' => 1],
                ]
            )
            ->andReturn([(object) ['migration' => 'bar']]);

        // Actions
        $result = $repository->getRan();

        // Assertions
        $this->assertSame(['bar'], $result);
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 19;
        $migration1 = new stdClass();
        $migration1->_id = new ObjectId();
        $migration1->batch = $batchNumber;
        $migration1->migration = 'create_users_index';

        $migration2 = new stdClass();
        $migration2->_id = new ObjectId();
        $migration2->batch = $batchNumber;
        $migration2->migration = 'create_products_index';

        $migrations = [$migration1, $migration2];
        $cursor = SplFixedArray::fromArray($migrations);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        $collection->expects()
            ->find(
                ['batch' => $batchNumber],
                ['sort' => ['migration' => -1]]
            )
            ->andReturn($cursor);

        // Actions
        $result = $repository->getLast();

        // Assertions
        $this->assertSame($migrations, $result);
    }

    public function testLogMethodInsertsRecordIntoMigrationCollection()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->insertOne(['migration' => 'bar', 'batch' => 1]);

        // Actions
        $repository->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheCollection()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');
        $migration = (object) ['migration' => 'create_foo_index'];

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->deleteOne(['migration' => 'create_foo_index']);


        // Actions
        $repository->delete($migration);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 4;

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('mongolid', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        // Actions
        $result = $repository->getNextBatchNumber();

        // Assertions
        $this->assertSame(5, $result);
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'mongolid';
        $client = m::mock(Client::class);
        $collection = m::mock(Collection::class);

        $batchNumber = 4;

        // Expectations
        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectCollection('testing', 'migrations')
            ->andReturn($collection);

        $collection->expects()
            ->find(
                [],
                [
                    'projection' => ['_id' => 0, 'batch' => 1],
                    'sort' => ['batch' => -1],
                    'limit' => 1,
                ]
            )
            ->andReturn(SplFixedArray::fromArray([(object) ['batch' => $batchNumber]]));

        // Actions
        $repository->setSource('testing');
        $result = $repository->getLastBatchNumber();

        // Assertions
        $this->assertSame($batchNumber, $result);
    }

    public function testCreateRepositoryCreatesProperDatabaseCollection()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        // Expectations
        $pool->expects()
            ->getConnection()
            ->never();

        // Actions
        $repository->createRepository();
    }

    public function testRepositoryExists()
    {
        // Set
        $pool = m::mock(Pool::class);
        $repository = new MongolidMigrationRepository($pool, 'migrations');

        // Expectations
        $pool->expects()
            ->getConnection()
            ->never();

        // Actions
        $result = $repository->repositoryExists();

        // Assertions
        $this->assertTrue($result);
    }
}