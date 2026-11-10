<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api\notifications;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests the NotificationResource Show endpoint added for realtime payload generation.
 */
class ShowNotificationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // The Show endpoint lives in core; no extra extension needed.
        // We just need a notification in the database.

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'A discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
            Notification::class => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'postMentioned', 'subject_id' => 1, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    #[Test]
    public function owner_can_fetch_their_notification(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications/1', ['authenticatedAs' => 2])
        );

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame('notifications', $body['data']['type']);
        $this->assertSame('1', $body['data']['id']);
    }

    #[Test]
    public function notification_includes_from_user(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications/1', ['authenticatedAs' => 2])
        );

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $included = collect($body['included'] ?? []);

        // fromUser (from_user_id=1) should be included
        $this->assertTrue($included->contains(fn ($item) => $item['type'] === 'users' && $item['id'] === '1'));
    }

    #[Test]
    public function another_user_cannot_fetch_someone_elses_notification(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications/1', ['authenticatedAs' => 3])
        );

        // The scope() gates by user_id = actor->id, so the notification is not found.
        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_cannot_fetch_notification(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications/1')
        );

        // The endpoint is authenticated(); guests get 401 or 404 depending on
        // whether the auth check fires before or after the scope check.
        $this->assertContains($response->getStatusCode(), [401, 404]);
    }

    #[Test]
    public function admin_cannot_fetch_another_users_notification(): void
    {
        // Even admin is blocked by the user_id scope — notifications are strictly personal.
        $response = $this->send(
            $this->request('GET', '/api/notifications/1', ['authenticatedAs' => 1])
        );

        $this->assertSame(404, $response->getStatusCode());
    }
}
