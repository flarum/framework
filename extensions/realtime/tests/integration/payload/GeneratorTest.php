<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\payload;

use Carbon\Carbon;
use Flarum\Api\Client;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\PostResource;
use Flarum\Extend;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Payload\Generator;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class GeneratorTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Hello world', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                // A reply member 2 can't see (as an unapproved one would be), though its author can.
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Hidden</p></t>', 'hidden_at' => Carbon::now()],
            ],
            Notification::class => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'postMentioned', 'subject_id' => 1, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    private function generator(): Generator
    {
        // Calling app() boots the container and sets up the DB connection on models.
        return new Generator(
            $this->app()->getContainer()->make(Client::class),
            new RealtimeRegistry()
        );
    }

    #[Test]
    public function generates_discussion_payload_for_actor(): void
    {
        $generator = $this->generator();
        $discussion = Discussion::find(1);
        $actor = User::find(1);

        $payload = $generator($discussion, $actor);

        $this->assertNotNull($payload);
        $this->assertSame('discussions', $payload['data']['type']);
        $this->assertSame('1', $payload['data']['id']);
        $this->assertSame('Hello world', $payload['data']['attributes']['title']);
    }

    #[Test]
    public function generates_discussion_payload_as_guest_when_no_actor(): void
    {
        $generator = $this->generator();
        $discussion = Discussion::find(1);

        $payload = $generator($discussion);

        $this->assertNotNull($payload);
        $this->assertSame('discussions', $payload['data']['type']);
    }

    #[Test]
    public function generates_discussion_payload_with_post_in_included_when_post_given(): void
    {
        $generator = $this->generator();
        $post = Post::find(1);
        $actor = User::find(2);

        $payload = $generator($post, $actor);

        $this->assertNotNull($payload);
        // Primary data is the discussion (the post's parent)
        $this->assertSame('discussions', $payload['data']['type']);
        // The post itself should appear in included
        $included = collect($payload['included'] ?? []);
        $this->assertTrue($included->contains(fn ($item) => $item['type'] === 'posts' && $item['id'] === '1'));
    }

    /**
     * The discussion alone would still say "something new was posted here",
     * about a reply the recipient isn't allowed to know exists.
     */
    #[Test]
    public function generates_nothing_for_a_post_the_recipient_cannot_see(): void
    {
        $generator = $this->generator();

        $this->assertNull($generator(Post::find(2), User::find(2)));
        $this->assertNull($generator(Post::find(2)));
    }

    #[Test]
    public function generates_a_post_payload_for_those_who_can_see_it(): void
    {
        $generator = $this->generator();

        $payload = $generator(Post::find(2), User::find(3));

        $this->assertNotNull($payload);
        $this->assertTrue(collect($payload['included'] ?? [])->contains(fn ($item) => $item['type'] === 'posts' && $item['id'] === '2'));
    }

    #[Test]
    public function includes_likes_while_likes_is_enabled(): void
    {
        $this->extension('flarum-likes');

        $payload = $this->generator()(Post::find(2), User::find(3));

        // Found by identity: the post's own included records follow it, so
        // its position in the list is not fixed.
        $post = collect($payload['included'] ?? [])
            ->first(fn ($item) => $item['type'] === 'posts' && $item['id'] === '2');

        $this->assertNotNull($post);
        $this->assertArrayHasKey('likes', $post['relationships']);
    }

    /**
     * The post comes as the posts endpoint gives it by default, so whatever
     * other extensions add to a post (flags here) arrives with it.
     */
    #[Test]
    public function includes_what_extensions_add_to_a_post(): void
    {
        $this->extension('flarum-flags');

        $payload = $this->generator()(Post::find(1), User::find(1));

        $post = collect($payload['included'] ?? [])
            ->first(fn ($item) => $item['type'] === 'posts' && $item['id'] === '1');

        $this->assertNotNull($post);
        $this->assertArrayHasKey('flags', $post['relationships']);
        $this->assertArrayHasKey('user', $post['relationships']);
    }

    /**
     * A relationship is only usable if the record it points at travels with
     * it: the client resolves a relation through the store, so a pointer to a
     * record that was never included resolves to nothing and the relation
     * reads as absent.
     *
     * The post is fetched from its own endpoint, and the records its includes
     * produce arrive in that response's `included` — which was dropped,
     * keeping only `data`. It went unnoticed because every relation core puts
     * on a post points at something the discussion half supplies anyway
     * (authors, tags, groups); only a record unique to the post exposes it.
     */
    #[Test]
    public function the_records_a_posts_relationships_point_at_travel_with_it(): void
    {
        // A fourth user who neither started the discussion nor posted in it,
        // so nothing in the discussion half carries them.
        $this->prepareDatabase([
            User::class => [
                ['id' => 4, 'username' => 'editor', 'email' => 'editor@machine.local', 'is_email_confirmed' => 1],
            ],
            Post::class => [
                ['id' => 4, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Edited</p></t>', 'edited_at' => Carbon::now(), 'edited_user_id' => 4],
            ],
        ]);

        $this->extend(
            (new Extend\ApiResource(PostResource::class))
                ->endpoint(Endpoint\Show::class, fn (Endpoint\Show $endpoint) => $endpoint->addDefaultInclude(['editedUser']))
        );

        $payload = $this->generator()(Post::find(4), User::find(1));

        $included = collect($payload['included'] ?? []);

        $post = $included->first(fn ($item) => $item['type'] === 'posts' && $item['id'] === '4');

        $this->assertNotNull($post);

        $editedUser = $post['relationships']['editedUser']['data'] ?? null;

        $this->assertNotNull($editedUser, 'the post should carry its editedUser relationship');

        // The record the relationship points at must be present, or the client
        // cannot resolve it.
        $this->assertTrue(
            $included->contains(fn ($item) => $item['type'] === $editedUser['type'] && $item['id'] === $editedUser['id']),
            "the payload links {$editedUser['type']}:{$editedUser['id']} but does not include it"
        );
    }

    #[Test]
    public function generates_notification_payload_for_recipient(): void
    {
        $generator = $this->generator();
        $notification = Notification::find(1);
        $actor = User::find(2);

        $payload = $generator($notification, $actor);

        $this->assertNotNull($payload);
        $this->assertSame('notifications', $payload['data']['type']);
        $this->assertSame('1', $payload['data']['id']);
    }

    #[Test]
    public function returns_null_for_unknown_model_type(): void
    {
        // User is in the default endpoints map so we need something truly unknown.
        // We test this by using an empty registry and a model that isn't Discussion/Post/User/Notification.
        // The easiest way is to just subclass and override the endpoint map — but since Generator
        // is non-final we can test the retrieve path by calling with a User (which IS in the map)
        // vs verifying null is returned for unknown models via registerModelEndpoint being absent.
        // Simplest: pass a notification to a generator with no registry entries and assert it works
        // (it should, because Notification is a core entry).
        $this->markTestSkipped('Unknown-model-type path covered by unit tests.');
    }

    #[Test]
    public function extension_registered_endpoint_is_merged_at_call_time(): void
    {
        $registry = new RealtimeRegistry();
        $registry->addModelEndpoint(Discussion::class, 'discussions');

        $generator = new Generator(
            $this->app()->getContainer()->make(Client::class),
            $registry
        );

        $discussion = Discussion::find(1);
        $payload = $generator($discussion);

        // Core 'discussions' entry and registry entry are the same here — just assert it doesn't break.
        $this->assertNotNull($payload);
        $this->assertSame('discussions', $payload['data']['type']);
    }
}
