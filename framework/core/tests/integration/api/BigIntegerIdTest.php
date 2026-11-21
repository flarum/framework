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
use Flarum\Extend;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A `bigint` key holds ids beyond the 32-bit range, so such an id must still
 * find its record. Unsubscribe tokens use a `bigint` key, which an extension
 * resource could expose.
 */
class BigIntegerIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(new Extend\ApiResource(BigIntegerKeyResource::class));

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

        $table = $db->getTablePrefix().'unsubscribe_tokens';

        $db->statement('create schema if not exists later_schema');
        $db->statement("create table later_schema.\"$table\" (like public.\"$table\" including all)");
        $db->statement("insert into later_schema.\"$table\" (id, user_id, email_type, token, created_at) values (2147483649, 1, 'test', 'def', now())");

        try {
            $db->statement("alter table public.\"$table\" rename to \"{$table}_hidden\"");
            $db->statement('set search_path to public, later_schema');

            $response = $this->send(
                $this->request('GET', '/api/big-integer-keys/2147483649', ['authenticatedAs' => 1])
            );

            $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        } finally {
            $db->statement('set search_path to public');
            $db->statement("alter table public.\"{$table}_hidden\" rename to \"$table\"");
            $db->statement('drop schema later_schema cascade');
        }
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
