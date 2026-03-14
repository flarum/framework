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

class UpdateTest extends TestCase
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
                ['id' => 3, 'username' => 'attacker', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'attacker@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Foo', 'comment_count' => 1, 'user_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => 'Foo'],
            ],
            Notification::class => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()],
            ]
        ]);
    }

    #[Test]
    public function can_mark_all_as_read()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/notifications/1', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'notifications',
                        'attributes' => [
                            'isRead' => true
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function user_cannot_read_another_users_notification()
    {
        // Attacker (user 3) sends an empty PATCH for a notification belonging to user 2.
        // Without the ownership scope this returns 200 and leaks the notification content.
        $response = $this->send(
            $this->request('PATCH', '/api/notifications/1', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'notifications',
                        'id' => '1',
                    ],
                ],
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_cannot_mark_another_users_notification_as_read()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/notifications/1', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'notifications',
                        'id' => '1',
                        'attributes' => [
                            'isRead' => true,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());

        // Confirm the notification is still unread.
        $this->assertNull(Notification::find(1)->read_at);
    }

    #[Test]
    public function admin_cannot_read_another_users_notification()
    {
        // Notifications are personal — even admins should not be able to retrieve
        // another user's notification via the update endpoint.
        $response = $this->send(
            $this->request('PATCH', '/api/notifications/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'notifications',
                        'id' => '1',
                    ],
                ],
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_cannot_update_notification()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/notifications/1', [
                'json' => [
                    'data' => [
                        'type' => 'notifications',
                        'id' => '1',
                    ],
                ],
            ])
        );

        // 401 is ideal but the JSON:API layer rejects the malformed body first; either way guests are blocked.
        $this->assertNotEquals(200, $response->getStatusCode());
    }
}
