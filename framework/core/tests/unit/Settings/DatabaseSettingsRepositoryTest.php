<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Settings;

use Flarum\Settings\DatabaseSettingsRepository;
use Flarum\Testing\unit\TestCase;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;

class DatabaseSettingsRepositoryTest extends TestCase
{
    private $connection;
    private $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = new DatabaseSettingsRepository($this->connection);
    }

    public function test_requesting_an_existing_setting_should_return_its_value()
    {
        $this->connection->shouldReceive('table->where->value')->andReturn('value');

        $this->assertEquals('value', $this->repository->get('key'));
    }

    public function test_non_existent_setting_values_should_return_null()
    {
        $this->connection->shouldReceive('table->where->value')->andReturn(null);

        $this->assertEquals('default', $this->repository->get('key', 'default'));
    }

    public function test_setting_a_value_writes_it_in_a_single_statement()
    {
        // One upsert, keyed on the table's primary key. A read-then-write pair
        // lets two writers both decide the row is missing and both insert it,
        // and the loser gets a duplicate-key error rather than a stored value.
        $table = m::mock();
        $table->shouldReceive('upsert')
            ->once()
            ->with(['key' => 'foo', 'value' => 'bar'], ['key'], ['value'])
            ->andReturn(1);

        $table->shouldNotReceive('exists');
        $table->shouldNotReceive('insert');
        $table->shouldNotReceive('update');
        $table->shouldNotReceive('where');

        $this->connection->shouldReceive('table')->with('settings')->once()->andReturn($table);

        $this->repository->set('foo', 'bar');
    }

    public function test_setting_a_null_value_is_stored_rather_than_skipped()
    {
        // `value` is nullable, and a null is a real stored setting — distinct
        // from an absent row, which is what makes get() fall back to a default.
        $table = m::mock();
        $table->shouldReceive('upsert')
            ->once()
            ->with(['key' => 'foo', 'value' => null], ['key'], ['value'])
            ->andReturn(1);

        $this->connection->shouldReceive('table')->with('settings')->once()->andReturn($table);

        $this->repository->set('foo', null);
    }

    public function test_deleting_matches_on_the_key()
    {
        $table = m::mock();
        $table->shouldReceive('where')->once()->with('key', 'foo')->andReturnSelf();
        $table->shouldReceive('delete')->once()->andReturn(1);

        $this->connection->shouldReceive('table')->with('settings')->once()->andReturn($table);

        $this->repository->delete('foo');
    }
}
