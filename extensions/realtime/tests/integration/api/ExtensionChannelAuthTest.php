<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Realtime\Extend\Realtime as RealtimeExtender;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * An extension can register its own websocket channels and have them authorized
 * against its own permission.
 *
 * The point of the exercise is the channel that is *narrower* than the discussion
 * it belongs to: `acme-readers` here is registered against a permission only some
 * of the people who can see the discussion hold. Before channels were
 * registrable, an extension had no way to obtain one and had to carry its data on
 * a channel realtime already defined — inheriting that channel's audience, which
 * for the discussion typing channel is everyone who can see the discussion,
 * guests included.
 */
class ExtensionChannelAuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->extend(
            (new RealtimeExtender())
                ->privateChannel('acme-readers', function (User $actor, int $id) {
                    return $actor->hasPermission('acme-readers.view')
                        && Discussion::whereVisibleTo($actor)->where('id', $id)->exists();
                })
                ->presenceChannel('acme-readers', function (User $actor, ?int $id) {
                    if ($id === null || ! $actor->hasPermission('acme-readers.view')) {
                        return false;
                    }

                    return ['displayName' => $actor->display_name];
                })
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2, Members — can see the discussion, not the roster
                ['id' => 3, 'username' => 'reader', 'email' => 'reader@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Reader', 'name_plural' => 'Readers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100],
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'acme-readers.view'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visible', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'],
            ],
        ]);
    }

    private function authorize(string $channel, ?int $actorId): int
    {
        $options = ['json' => ['channel_name' => $channel, 'socket_id' => '123.456']];

        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        return $this->send(
            $this->request('POST', '/api/websocket/auth', $options)
        )->getStatusCode();
    }

    #[Test]
    public function extension_private_channel_is_authorized_by_its_own_permission(): void
    {
        $this->assertSame(200, $this->authorize('private-acme-readers=1', 3));
    }

    /**
     * The whole point: user 2 may see the discussion — and is admitted to realtime's
     * own typing channel for it — but holds no `acme-readers.view`, so the
     * extension's channel is closed to them.
     */
    #[Test]
    public function seeing_the_discussion_does_not_grant_the_extension_channel(): void
    {
        $this->assertSame(200, $this->authorize('private-typing=1', 2));
        $this->assertSame(403, $this->authorize('private-acme-readers=1', 2));
    }

    #[Test]
    public function extension_private_channel_can_exclude_guests(): void
    {
        // Guests are admitted to the discussion's typing channel, so this is a
        // narrowing the extension could not previously express.
        $this->assertSame(200, $this->authorize('private-typing=1', null));
        $this->assertSame(403, $this->authorize('private-acme-readers=1', null));
    }

    #[Test]
    public function extension_channel_still_checks_the_object(): void
    {
        // Holding the permission is not visibility of the thing it names.
        $this->assertSame(403, $this->authorize('private-acme-readers=404', 3));
    }

    /**
     * Presence channels used to be forum-wide by construction — the subject pattern
     * admitted no id — which is why a per-discussion roster could not use one.
     */
    #[Test]
    public function extension_presence_channel_can_be_scoped_to_an_object(): void
    {
        $this->assertSame(200, $this->authorize('presence-acme-readers=1', 3));
        $this->assertSame(403, $this->authorize('presence-acme-readers=1', 2));
    }

    #[Test]
    public function presence_channels_refuse_guests(): void
    {
        $this->assertSame(403, $this->authorize('presence-acme-readers=1', null));
    }

    #[Test]
    public function an_unregistered_subject_is_refused(): void
    {
        $this->assertSame(403, $this->authorize('private-acme-nothing=1', 3));
        $this->assertSame(403, $this->authorize('presence-acme-nothing', 3));
    }

    /**
     * Subjects used to be resolved with `method_exists()` on the controller, so any
     * method name was a channel name: the controller would call `handle('1')` or
     * `online('1')` and die on the argument type, handing an unauthenticated caller
     * a 500. Channels come from the registry now, and a name that is not registered
     * is simply refused.
     */
    #[Test]
    public function a_controller_method_name_is_not_a_channel_name(): void
    {
        $this->assertSame(403, $this->authorize('private-handle=1', null));
        $this->assertSame(403, $this->authorize('private-handle=1', 3));

        // `online` is a presence subject, so it is not a private channel either.
        $this->assertSame(403, $this->authorize('private-online=1', null));
    }

    /**
     * `private-index-typing-tag={id}` needed its own branch in the controller
     * because the subject pattern rejected hyphens. It is an ordinary registration
     * now, so the pattern has to accept them.
     */
    #[Test]
    public function built_in_channels_still_authorize(): void
    {
        $this->assertSame(200, $this->authorize('private-user=2', 2));
        $this->assertSame(403, $this->authorize('private-user=3', 2));
        $this->assertSame(200, $this->authorize('presence-online', 2));
    }
}
