<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The enough-tags verdict depends only on the actor's groups, so it is worked
 * out once per group set, however many User instances ask. Rendering a post
 * that links to a discussion looks up the discussion's title, a visibility-
 * scoped query that asks viewForum; rendered without a request, as fof/seo
 * renders every reply, each post asks as a new Guest.
 */
class GlobalPolicyRequestCostTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const POSTS = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $date = Carbon::parse('2020-01-01')->toDateTimeString();
        $posts = [];

        foreach (range(1, self::POSTS) as $number) {
            $posts[] = [
                'id' => $number,
                'discussion_id' => 1,
                'number' => $number,
                'created_at' => $date,
                'user_id' => 1,
                'type' => 'comment',
                'content' => '<r><URL url="http://localhost/d/2">http://localhost/d/2</URL></r>',
            ];
        }

        $posts[] = ['id' => 100, 'discussion_id' => 2, 'number' => 1, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t>Linked</t>'];

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Tag::class => [
                ['id' => 1, 'name' => 'Primary', 'slug' => 'primary', 'position' => 0],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Linking', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => self::POSTS, 'last_post_number' => self::POSTS],
                ['id' => 2, 'title' => 'Linked', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 100, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => $posts,
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
            ],
        ]);

        $this->setting('flarum-tags.min_primary_tags', '1');
        $this->setting('flarum-tags.min_secondary_tags', '0');
    }

    /**
     * @param callable(): void $work
     */
    private function tagCountQueries(callable $work): int
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $work();

        $queries = $db->getQueryLog();
        $db->disableQueryLog();

        return count(array_filter($queries, fn (array $query) => str_contains($query['query'], 'primary_count')));
    }

    #[Test]
    public function posts_rendered_without_a_request_count_the_tags_once()
    {
        $this->app();

        $posts = CommentPost::query()->where('discussion_id', 1)->get();
        $this->assertCount(self::POSTS, $posts);

        $count = $this->tagCountQueries(function () use ($posts) {
            foreach ($posts as $post) {
                $this->assertStringContainsString('Linked', $post->formatContent(), 'the linked discussion\'s title was looked up');
            }
        });

        $this->assertSame(1, $count);
    }

    #[Test]
    public function users_sharing_groups_count_the_tags_once()
    {
        $this->app();

        $count = $this->tagCountQueries(function () {
            foreach (range(1, self::POSTS) as $_) {
                $this->assertTrue(User::query()->findOrFail(2)->can('viewForum'));
            }
        });

        $this->assertSame(1, $count);
    }

    #[Test]
    public function a_changed_minimum_is_not_answered_from_the_cache()
    {
        $this->app();

        // startDiscussion, as a minimum above the forum's tag count still
        // lets an actor who sees every tag view the forum.
        $this->assertTrue(User::query()->findOrFail(2)->can('startDiscussion'));

        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('flarum-tags.min_primary_tags', '2');

        $this->assertFalse(User::query()->findOrFail(2)->can('startDiscussion'));
    }
}
