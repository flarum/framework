<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Database\AbstractModel;
use Flarum\Extend;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unsubscribe tokens have a `bigint` key and need no other fixtures.
 */
class BigIntegerIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            new Extend\ApiResource(BigIntegerKeyResource::class),
            new Extend\ApiResource(QualifiedIntegerKeyResource::class),
        );

        $this->prepareDatabase([
            UnsubscribeToken::class => [
                ['id' => 2147483648, 'user_id' => 1, 'email_type' => 'test', 'token' => 'abc', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);
    }

    #[Test]
    public function id_beyond_the_32_bit_range_finds_the_record()
    {
        $response = $this->send(
            $this->request('GET', '/api/big-integer-keys/2147483648', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('2147483648', json_decode((string) $response->getBody(), true)['data']['id']);
    }

    #[Test]
    public function id_beyond_the_32_bit_range_finds_a_record_in_a_later_schema()
    {
        $db = $this->database();

        if ($db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Schemas are a PostgreSQL feature.');
        }

        $grammar = $db->getQueryGrammar();
        $schema = $db->selectOne('select current_schema() as name')->name;
        $table = $db->getTablePrefix().'unsubscribe_tokens';
        $original = $grammar->wrapTable("$schema.unsubscribe_tokens");
        $hidden = $grammar->wrap("{$table}_hidden");
        $later = $grammar->wrapTable('flarum_test_later_schema.unsubscribe_tokens');

        $db->statement('create schema if not exists flarum_test_later_schema');
        $db->statement("create table $later (like $original including all)");
        $db->statement("insert into $later (id, user_id, email_type, token, created_at) values (2147483649, 1, 'test', 'def', now())");

        $db->statement("alter table $original rename to $hidden");
        $db->statement('select set_config(\'search_path\', ?, false)', [$grammar->wrap($schema).', flarum_test_later_schema']);

        $response = $this->send(
            $this->request('GET', '/api/big-integer-keys/2147483649', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        // Without the column type, the guard would let this id through to a range error.
        $response = $this->send(
            $this->request('GET', '/api/big-integer-keys/9223372036854775808', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function id_beyond_the_32_bit_range_is_not_found_in_a_schema_qualified_integer_table()
    {
        $db = $this->database();

        if ($db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Schemas are a PostgreSQL feature.');
        }

        $table = $db->getQueryGrammar()->wrapTable((new QualifiedIntegerKeyModel)->getTable());

        $db->statement('create schema if not exists flarum_test_qualified_schema');
        $db->statement("create table $table (id serial primary key)");
        $db->statement("insert into $table (id) values (1)");

        $response = $this->send(
            $this->request('GET', '/api/qualified-integer-keys/1', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $response = $this->send(
            $this->request('GET', '/api/qualified-integer-keys/2147483648', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function id_beyond_the_bigint_range_is_not_found()
    {
        $response = $this->send(
            $this->request('GET', '/api/big-integer-keys/9223372036854775808', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }
}

/**
 * @extends AbstractDatabaseResource<UnsubscribeToken>
 */
class BigIntegerKeyResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'big-integer-keys';
    }

    public function model(): string
    {
        return UnsubscribeToken::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('emailType')->property('email_type'),
        ];
    }
}

class QualifiedIntegerKeyModel extends AbstractModel
{
    protected $table = 'flarum_test_qualified_schema.integer_keys';

    /**
     * Laravel prefixes the first segment of a qualified column, which here is
     * the schema, so the key is left unqualified for prefixed test runs.
     */
    public function qualifyColumn($column)
    {
        return $column;
    }
}

/**
 * @extends AbstractDatabaseResource<QualifiedIntegerKeyModel>
 */
class QualifiedIntegerKeyResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'qualified-integer-keys';
    }

    public function model(): string
    {
        return QualifiedIntegerKeyModel::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
        ];
    }

    public function fields(): array
    {
        return [];
    }
}
