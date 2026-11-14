<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * `private-typing-activity` carries who is typing where across the whole forum,
 * so joining it needs `flarum-realtime.view-all-typing`, which no group has by
 * default: only admins can join until it's granted.
 */
class TypingActivityAuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2, Members
                ['id' => 3, 'username' => 'watcher', 'email' => 'watcher@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Watcher', 'name_plural' => 'Watchers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100],
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'flarum-realtime.view-all-typing'],
            ],
        ]);
    }

    private function authorize(?int $actorId): int
    {
        $options = ['json' => ['channel_name' => 'private-typing-activity', 'socket_id' => '123.456']];

        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        return $this->send($this->request('POST', '/api/websocket/auth', $options))->getStatusCode();
    }

    #[Test]
    public function admins_can_join_by_default(): void
    {
        $this->assertSame(200, $this->authorize(1));
    }

    #[Test]
    public function members_cannot_join_by_default(): void
    {
        $this->assertSame(403, $this->authorize(2));
    }

    #[Test]
    public function guests_cannot_join(): void
    {
        $this->assertSame(403, $this->authorize(null));
    }

    #[Test]
    public function a_group_granted_the_permission_can_join(): void
    {
        $this->assertSame(200, $this->authorize(3));
    }

    #[Test]
    public function nobody_can_join_while_the_typing_indicator_is_off(): void
    {
        $this->setting('flarum-realtime.typing-indicator', false);

        $this->assertSame(403, $this->authorize(1));
    }
}
