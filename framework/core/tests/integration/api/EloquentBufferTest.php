<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Resource\EloquentBuffer;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Tobyz\JsonApiServer\JsonApi;

class EloquentBufferTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'a', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'b', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'c', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'comment_count' => 1],
            ],
        ]);
    }

    #[Test]
    public function relations_loaded_before_serialization_are_not_left_in_the_buffer()
    {
        $this->app();

        // The discussion list includes `user` by default and eager loads it
        // before serializing, so there is nothing for the buffer to load.
        // Only load() empties the buffer, so a model queued anyway would stay
        // for the rest of the process and be loaded again by later requests.
        foreach ([1, 2] as $request) {
            $response = $this->send(
                $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
            );

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertCount(0, EloquentBuffer::getBuffer(new Discussion(), 'user') ?? [], "After request $request");
        }
    }

    #[Test]
    public function aggregate_load_does_not_crash_for_models_that_do_not_exist_in_the_database()
    {
        $this->app();

        // Simulate the JSON:API BelongsTo linkage shortcut for an orphaned FK:
        // a model created via newInstance()->forceFill([key => $orphanId]) with
        // exists=false. Without a guard, EloquentBuffer::load() would forward
        // this to Laravel's Collection::loadAggregate(), which fatals when
        // mapping results because the orphaned key has no matching DB row.
        $orphan = (new User())->forceFill(['id' => 999999]);

        $aggregate = [
            'name' => 'postCount',
            'relation' => 'posts',
            'column' => '*',
            'function' => 'count',
            'constrain' => null,
        ];

        EloquentBuffer::add($orphan, 'posts', $aggregate);

        EloquentBuffer::load(
            $orphan,
            'posts',
            null,
            new Context(new JsonApi(), new ServerRequest()),
            $aggregate,
        );

        $this->assertFalse($orphan->exists);
    }
}
