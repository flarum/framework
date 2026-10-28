<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Likes' user filter appears in the core users descriptor.
 */
class UsersSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function liked_filter_is_described_as_a_post_relationship()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $filters = array_column(json_decode($body, true)['data']['attributes']['filters'], null, 'key');

        // `filter[liked]=<postId>` lists the users who liked that post.
        $this->assertArrayHasKey('liked', $filters);
        $this->assertSame('relationship', $filters['liked']['kind']);
        $this->assertSame('posts', $filters['liked']['related']);
    }

    #[Test]
    public function liked_filter_is_described_to_a_member()
    {
        // `LikedFilter::filter()` has no permission gate, so neither has its description.
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 2])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $filters = array_column(json_decode($body, true)['data']['attributes']['filters'], null, 'key');

        $this->assertArrayHasKey('liked', $filters);
    }
}
