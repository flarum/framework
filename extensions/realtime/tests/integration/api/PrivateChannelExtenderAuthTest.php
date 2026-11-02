<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Flarum\Realtime\Extend\Realtime;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A private channel registered by another extension through
 * `Extend\Realtime::authorizePrivateChannel()` is authorized by that
 * extension's callback, and by nothing else.
 */
class PrivateChannelExtenderAuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->extend(
            (new Realtime())
                // Members may join the channel named after their own id.
                ->authorizePrivateChannel('scratchpad', fn (User $actor, int $id) => ! $actor->isGuest() && $actor->id === $id)
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2
            ],
        ]);
    }

    private function authorize(string $channel, ?int $actorId): int
    {
        $options = ['json' => ['channel_name' => $channel, 'socket_id' => '123.456']];

        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        return $this->send($this->request('POST', '/api/websocket/auth', $options))->getStatusCode();
    }

    #[Test]
    public function the_callback_grants_access(): void
    {
        $this->assertSame(200, $this->authorize('private-scratchpad=2', 2));
    }

    #[Test]
    public function the_callback_denies_access(): void
    {
        $this->assertSame(403, $this->authorize('private-scratchpad=1', 2));
    }

    #[Test]
    public function guests_get_whatever_the_callback_decides(): void
    {
        $this->assertSame(403, $this->authorize('private-scratchpad=2', null));
    }

    #[Test]
    public function another_spelling_of_the_same_id_is_denied(): void
    {
        $this->assertSame(403, $this->authorize('private-scratchpad=02', 2));
        $this->assertSame(403, $this->authorize("private-scratchpad=2\n", 2));
    }

    #[Test]
    public function an_unregistered_subject_is_still_denied(): void
    {
        $this->assertSame(403, $this->authorize('private-somethingElse=2', 1));
    }

    #[Test]
    public function realtimes_own_channels_are_unaffected(): void
    {
        $this->assertSame(200, $this->authorize('private-user=2', 2));
        $this->assertSame(403, $this->authorize('private-user=1', 2));
        $this->assertSame(403, $this->authorize("private-user=2\n", 2));
    }
}
