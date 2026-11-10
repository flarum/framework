<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Deletion mode removes the user's posts in bulk, so nothing that normally
 * reacts to a post being deleted runs. The discussions they posted in must
 * still be left as if each post had been deleted.
 *
 * @see https://github.com/flarum/framework/issues/5010
 */
class DeletionModeDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-gdpr');

        $this->extend((new Extend\Notification())->type(PostSubjectBlueprint::class));

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-gdpr.allow-deletion', true);

        $at = fn (int $minute) => Carbon::parse('2026-01-01 00:00:00')->addMinutes($minute);

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'erased', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'erased@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
            ],
            Discussion::class => [
                // Started by the erased user; others replied.
                ['id' => 1, 'title' => 'Started by them', 'user_id' => 5, 'first_post_id' => 1, 'last_post_id' => 3, 'last_post_number' => 3, 'last_posted_user_id' => 3, 'last_posted_at' => $at(3), 'comment_count' => 3, 'participant_count' => 3, 'created_at' => $at(1)],
                // Started by someone else; the erased user has the last reply.
                ['id' => 2, 'title' => 'Replied to by them', 'user_id' => 2, 'first_post_id' => 4, 'last_post_id' => 5, 'last_post_number' => 2, 'last_posted_user_id' => 5, 'last_posted_at' => $at(5), 'comment_count' => 2, 'participant_count' => 2, 'created_at' => $at(4)],
                // Every post is theirs.
                ['id' => 3, 'title' => 'Only theirs', 'user_id' => 5, 'first_post_id' => 6, 'last_post_id' => 7, 'last_post_number' => 2, 'last_posted_user_id' => 5, 'last_posted_at' => $at(7), 'comment_count' => 2, 'participant_count' => 1, 'created_at' => $at(6)],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Opening</p></t>', 'created_at' => $at(1)],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(2)],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(3)],
                ['id' => 4, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Opening</p></t>', 'created_at' => $at(4)],
                ['id' => 5, 'discussion_id' => 2, 'number' => 2, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(5)],
                ['id' => 6, 'discussion_id' => 3, 'number' => 1, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Opening</p></t>', 'created_at' => $at(6)],
                ['id' => 7, 'discussion_id' => 3, 'number' => 2, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(7)],
            ],
            Notification::class => [
                // About the erased user's post.
                ['id' => 1, 'user_id' => 2, 'type' => PostSubjectBlueprint::getType(), 'subject_id' => 5, 'created_at' => $at(5)],
                // About someone else's post.
                ['id' => 2, 'user_id' => 3, 'type' => PostSubjectBlueprint::getType(), 'subject_id' => 2, 'created_at' => $at(2)],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'discussion_count' => 3, 'last_posted_discussion_id' => 3, 'last_posted_user_id' => 5, 'last_posted_at' => $at(7)],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 5, 'verification_token' => 'abc123', 'status' => 'user_confirmed', 'reason' => 'Forget me', 'created_at' => $at(8), 'user_confirmed_at' => $at(8)],
            ],
        ]);
    }

    protected function erase(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/1', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'Done',
                            'processedMode' => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(5));
    }

    #[Test]
    public function a_discussion_they_started_keeps_the_other_replies_and_starts_at_the_first_of_them()
    {
        $this->erase();

        $discussion = Discussion::findOrFail(1);

        $this->assertEquals(2, $discussion->first_post_id);
        $this->assertEquals(2, $discussion->comment_count);
        $this->assertEquals(2, $discussion->participant_count);
        $this->assertEquals(3, $discussion->last_post_id);
        // Still theirs, not credited to whoever replied first. (Deleting the
        // user empties the column where the database enforces the foreign
        // key, which SQLite does not.)
        $this->assertNotEquals(2, $discussion->user_id);
        $this->assertEquals('2026-01-01 00:01:00', $discussion->created_at->toDateTimeString());
    }

    #[Test]
    public function a_discussion_they_last_replied_to_ends_at_the_reply_before_theirs()
    {
        $this->erase();

        $discussion = Discussion::findOrFail(2);

        $this->assertEquals(1, $discussion->comment_count);
        $this->assertEquals(1, $discussion->participant_count);
        $this->assertEquals(4, $discussion->last_post_id);
        $this->assertEquals(1, $discussion->last_post_number);
        $this->assertEquals(2, $discussion->last_posted_user_id);
        $this->assertEquals('2026-01-01 00:04:00', $discussion->last_posted_at->toDateTimeString());
    }

    #[Test]
    public function a_discussion_left_without_posts_is_deleted()
    {
        $this->erase();

        $this->assertNull(Discussion::find(3));
    }

    #[Test]
    public function notifications_about_their_posts_are_deleted()
    {
        $this->erase();

        $this->assertNull(Notification::find(1));
        $this->assertNotNull(Notification::find(2));
    }

    #[Test]
    public function a_tag_counts_and_shows_only_its_remaining_discussions()
    {
        $this->erase();

        $tag = Tag::findOrFail(1);

        $this->assertEquals(2, $tag->discussion_count);
        $this->assertEquals(2, $tag->last_posted_discussion_id);
        $this->assertEquals(2, $tag->last_posted_user_id);
        $this->assertEquals('2026-01-01 00:04:00', $tag->last_posted_at->toDateTimeString());
    }

    #[Test]
    public function other_peoples_posts_are_kept()
    {
        $this->erase();

        $this->assertEqualsCanonicalizing([2, 3, 4], Post::query()->pluck('id')->all());
    }
}

class PostSubjectBlueprint implements BlueprintInterface
{
    public function __construct(public Post $post)
    {
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'gdprTestPostNotification';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
