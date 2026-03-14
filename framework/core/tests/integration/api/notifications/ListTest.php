<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notifications;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Foo', 'comment_count' => 1, 'user_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => 'Foo'],
            ],
            Notification::class => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function disallows_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function shows_index_for_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function listing_only_returns_own_notifications()
    {
        // User 3 has no notifications — the listing must not return user 2's notification.
        $response = $this->send(
            $this->request('GET', '/api/notifications', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $ids = array_column($body['data'], 'id');

        $this->assertNotContains('1', $ids);
        $this->assertEmpty($ids);
    }
}
