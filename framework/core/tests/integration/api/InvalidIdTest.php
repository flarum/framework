<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * An id that can't be a database id is "not found" on every driver. PostgreSQL
 * errors when such a value is compared with an integer column, where MySQL and
 * SQLite just match nothing, so without a check first these were a 500 there.
 */
class InvalidIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'A discussion', 'created_at' => '2026-01-01 00:00:00', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => '2026-01-01 00:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
        ]);
    }

    public static function invalidIds(): array
    {
        $ids = ['abc', '1abc', '1.5', '99999999999', '0'];
        $paths = ['/api/users/', '/api/discussions/', '/api/posts/', '/api/groups/', '/api/notifications/'];

        $cases = [];

        foreach ($paths as $path) {
            foreach ($ids as $id) {
                $cases[$path.$id] = [$path.$id];
            }
        }

        $cases['discussion slug'] = ['/api/discussions/abc?bySlug=1'];

        return $cases;
    }

    #[Test]
    #[DataProvider('invalidIds')]
    public function an_id_that_cannot_exist_is_not_found(string $path)
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function valid_ids_still_resolve()
    {
        foreach (['/api/users/1', '/api/discussions/1', '/api/posts/1', '/api/groups/1', '/api/discussions/1?bySlug=1'] as $path) {
            $response = $this->send(
                $this->request('GET', $path, ['authenticatedAs' => 1])
            );

            $this->assertEquals(200, $response->getStatusCode(), $path.': '.$response->getBody());
        }
    }
}
