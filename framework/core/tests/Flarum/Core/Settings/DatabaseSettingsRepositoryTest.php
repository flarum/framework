<?php
namespace Tests\Flarum\Core\Settings;

use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;
use Tests\Test\TestCase;

class DatabaseSettingsRepositoryTest extends TestCase
{
    private $connection;
    private $repository;

    public function init()
    {
        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = new DatabaseSettingsRepository($this->connection);
    }
    
    public function test_requesting_an_existing_setting_should_return_its_value()
    {
        $this->connection->shouldReceive("table->where->value")->andReturn('value');

        $this->assertEquals('value', $this->repository->get('key'));
    }

    public function test_non_existent_setting_values_should_return_null()
    {
        $this->connection->shouldReceive("table->where->value")->andReturn(null);

        $this->assertEquals('default', $this->repository->get('key', 'default'));
    }
}
