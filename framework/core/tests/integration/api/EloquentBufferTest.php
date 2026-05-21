<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Api\Context;
use Flarum\Api\Resource\EloquentBuffer;
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
        ]);
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
