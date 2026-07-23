<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notifications;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
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

    #[Test]
    public function index_does_not_fail_when_a_subject_type_has_no_discussion_relationship()
    {
        // Registering a notification type whose subject (User) has no discussion
        // relationship must not make the endpoint reject its own default include.
        // Before the fix, `subject.discussion` was added purely because more than
        // one subject type was registered, and the include validator then threw
        // "Invalid include [subject.discussion]" (a 400) for every request.
        $this->extend(
            (new Extend\Notification())->type(UserSubjectBlueprint::class, ['alert'])
        );

        $response = $this->send(
            $this->request('GET', '/api/notifications', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function subject_discussion_is_still_included_when_a_subject_type_has_a_discussion_relationship()
    {
        // A Post subject does have a discussion relationship, so `subject.discussion`
        // remains a valid default include and the discussion is still eager-loaded.
        $this->extend(
            (new Extend\Notification())->type(PostSubjectBlueprint::class, ['alert'])
        );

        $this->prepareDatabase([
            Notification::class => [
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'postSubjectBlueprint', 'subject_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/notifications', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $included = array_map(fn ($resource) => $resource['type'].':'.$resource['id'], $body['included'] ?? []);

        $this->assertContains('discussions:1', $included, 'The post subject\'s discussion should be eager-loaded.');
    }

    #[Test]
    public function subject_discussion_state_is_batch_loaded_without_an_n_plus_one()
    {
        // With Post-subject notifications, each notification's subject.discussion
        // exposes a per-actor `state`. It must be eager-loaded in a single batched
        // query, not one query per notification (the n+1 this PR also mitigates).
        $this->extend(
            (new Extend\Notification())->type(PostSubjectBlueprint::class, ['alert'])
        );

        $count = 8;
        $discussions = [];
        $posts = [];
        $notifications = [];
        $discussionUser = [];

        for ($i = 0; $i < $count; $i++) {
            $id = 10 + $i;
            $discussions[] = ['id' => $id, 'title' => 'D'.$id, 'comment_count' => 1, 'user_id' => 2, 'first_post_id' => $id];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'];
            $notifications[] = ['id' => $id, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'postSubjectBlueprint', 'subject_id' => $id, 'read_at' => null, 'created_at' => Carbon::now()];
            $discussionUser[] = ['user_id' => 2, 'discussion_id' => $id, 'last_read_post_number' => 1];
        }

        $this->prepareDatabase([
            Discussion::class => $discussions,
            Post::class => $posts,
            Notification::class => $notifications,
            'discussion_user' => $discussionUser,
        ]);

        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/notifications', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $batchedLoads = 0;
        $individualLoads = 0;

        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'discussion_user') === false) {
                continue;
            }

            if (stripos($query['query'], ' in (') !== false) {
                $batchedLoads++;
            } else {
                $individualLoads++;
            }
        }

        $db->disableQueryLog();

        $this->assertSame(
            0,
            $individualLoads,
            "Discussion states were loaded individually ($individualLoads times) instead of a single batched query — an n+1 on the notifications index."
        );
        $this->assertGreaterThanOrEqual(1, $batchedLoads, 'Expected the discussion states to be eager-loaded in a single batched query.');
    }
}

class UserSubjectBlueprint implements BlueprintInterface, AlertableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): mixed
    {
        return [];
    }

    public static function getType(): string
    {
        return 'userSubjectBlueprint';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }
}

class PostSubjectBlueprint implements BlueprintInterface, AlertableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): mixed
    {
        return [];
    }

    public static function getType(): string
    {
        return 'postSubjectBlueprint';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
