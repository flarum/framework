<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\integration;

use Carbon\Carbon;
use Flarum\Akismet\Testing\FakeAkismetProvider;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Flags\Event\Cleared;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class AkismetFlagTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval', 'flarum-akismet');

        $this->extend(
            (new Extend\ServiceProvider())->register(FakeAkismetProvider::class)
        );

        FakeAkismetProvider::reset();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Existing discussion', 'created_at' => Carbon::parse('2024-01-01'), 'last_posted_at' => Carbon::parse('2024-01-01'), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2024-01-01'), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post</p></t>'],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
            ],
        ]);
    }

    #[Test]
    public function approving_a_held_post_as_not_spam_removes_the_akismet_flag_but_keeps_user_flags()
    {
        $postId = $this->spamReplyWithUserFlag();

        FakeAkismetProvider::reset([new Response(200, [], 'Thanks for making the web a better place.')]);

        $cleared = $this->listenForCleared();

        $this->patchPost($postId, ['isApproved' => true]);

        $this->assertSame([$postId], $cleared->posts);

        $this->assertSame(0, Flag::query()->where('post_id', $postId)->where('type', 'akismet')->count());
        $this->assertSame(1, Flag::query()->where('post_id', $postId)->where('type', 'user')->count());
        $this->assertStringContainsString('submit-ham', (string) FakeAkismetProvider::$history[0]['request']->getUri());
    }

    #[Test]
    public function hiding_a_held_post_removes_the_akismet_flag_but_keeps_user_flags()
    {
        $postId = $this->spamReplyWithUserFlag();

        FakeAkismetProvider::reset([new Response(200, [], 'Thanks for making the web a better place.')]);

        $cleared = $this->listenForCleared();

        $this->patchPost($postId, ['isHidden' => true]);

        $this->assertSame([$postId], $cleared->posts);

        $this->assertSame(0, Flag::query()->where('post_id', $postId)->where('type', 'akismet')->count());
        $this->assertSame(1, Flag::query()->where('post_id', $postId)->where('type', 'user')->count());
    }

    #[Test]
    public function editing_a_held_post_keeps_the_akismet_flag()
    {
        $postId = $this->spamReplyWithUserFlag();

        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $this->patchPost($postId, ['content' => 'Still spam, edited.']);

        $this->assertFalse((bool) Post::query()->findOrFail($postId)->is_approved);
        // At least one: ValidatePost adds another Akismet flag for each held edit.
        $this->assertNotSame(0, Flag::query()->where('post_id', $postId)->where('type', 'akismet')->count());
    }

    protected function spamReplyWithUserFlag(): int
    {
        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => 'Cheap watches here.'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => 1]]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertFalse((bool) Post::query()->findOrFail($postId)->is_approved);
        $this->assertSame(1, Flag::query()->where('post_id', $postId)->where('type', 'akismet')->count());

        $this->database()->table('flags')->insert(
            ['post_id' => $postId, 'user_id' => 1, 'type' => 'user', 'reason' => 'spam', 'created_at' => Carbon::now()]
        );

        return $postId;
    }

    protected function patchPost(int $postId, array $attributes): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function listenForCleared(): object
    {
        $cleared = new class {
            public array $posts = [];
        };

        $this->app()->getContainer()->make(Dispatcher::class)->listen(
            Cleared::class,
            fn (Cleared $event) => $cleared->posts[] = $event->post->id
        );

        return $cleared;
    }
}
