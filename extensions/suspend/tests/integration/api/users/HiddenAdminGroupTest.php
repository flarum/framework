<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Whether a user can be suspended asks whether they are an admin. On a forum
 * that hides its admin group from those who may not see hidden groups, a
 * moderator must still be told an admin can't be suspended.
 */
class HiddenAdminGroupTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const MODERATOR = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => self::MODERATOR, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => Group::ADMINISTRATOR_ID, 'name_singular' => 'Admin', 'name_plural' => 'Admins', 'is_hidden' => 1],
            ],
            'group_user' => [
                // Moderators may suspend users.
                ['user_id' => self::MODERATOR, 'group_id' => Group::MODERATOR_ID],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Started by the admin', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>From the admin</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>From a member</p></t>'],
            ],
        ]);
    }

    /**
     * Every user in a response, primary or included, keyed by id.
     *
     * @return array<int, array>
     */
    private function users(string $path, array $query = []): array
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => self::MODERATOR])->withQueryParams($query)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $body = json_decode($body, true);

        $users = [];

        foreach ([...isset($body['data']['type']) ? [$body['data']] : $body['data'], ...$body['included'] ?? []] as $resource) {
            if ($resource['type'] === 'users') {
                $users[(int) $resource['id']] = $resource['attributes'];
            }
        }

        return $users;
    }

    #[Test]
    public function a_moderator_is_told_they_cannot_suspend_an_admin_in_a_hidden_group(): void
    {
        $admin = $this->users('/api/users/1')[1];

        $this->assertFalse($admin['canSuspend']);
        $this->assertArrayNotHasKey('suspendReason', $admin);
        $this->assertArrayNotHasKey('suspendMessage', $admin);
        $this->assertArrayNotHasKey('suspendedUntil', $admin);
    }

    #[Test]
    public function the_same_goes_for_the_admin_as_an_author(): void
    {
        foreach (['/api/discussions/1' => [], '/api/posts' => ['filter' => ['discussion' => '1']]] as $path => $query) {
            $users = $this->users($path, $query);

            $this->assertFalse($users[1]['canSuspend'], "$path offered to suspend the admin.");
        }
    }

    #[Test]
    public function a_moderator_can_still_suspend_a_member(): void
    {
        $users = $this->users('/api/posts', ['filter' => ['discussion' => '1']]);

        $this->assertTrue($users[2]['canSuspend']);
    }
}
