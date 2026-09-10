<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A relation named in an endpoint's eagerLoad() list is pre-loaded with a plain
 * loadMissing(), which bypasses EloquentBuffer::load() — the only place a
 * related resource's scope() (whereVisibleTo) is applied. The serialiser then
 * skips the scoped loader because the relation is already loaded, so the
 * relation used to be serialised with no visibility check at all.
 *
 * The discussion Show endpoint eager-loads `firstPost.user.groups`, which drags
 * in `firstPost`: a moderator-hidden or private opening post was returned in
 * full to anyone who could see the discussion.
 */
class EagerLoadedRelationVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Public discussion, hidden first post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Public discussion, private first post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Public discussion, visible first post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>hidden opening post</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>private opening post</p></t>', 'is_private' => 1],
                ['id' => 3, 'number' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>visible opening post</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'bystander', 'email' => 'bystander@machine.local', 'is_email_confirmed' => 1, 'password' => 'too-obscure'],
            ],
        ]);
    }

    private function showDiscussion(int $id, ?int $actor = null): string
    {
        $response = $this->send(
            $this->request('GET', "/api/discussions/$id", $actor ? ['authenticatedAs' => $actor] : [])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    #[Test]
    public function guest_does_not_receive_a_hidden_first_post()
    {
        $this->assertStringNotContainsString('hidden opening post', $this->showDiscussion(1));
    }

    #[Test]
    public function other_user_does_not_receive_a_hidden_first_post()
    {
        $this->assertStringNotContainsString('hidden opening post', $this->showDiscussion(1, 3));
    }

    #[Test]
    public function guest_does_not_receive_a_private_first_post()
    {
        $this->assertStringNotContainsString('private opening post', $this->showDiscussion(2));
    }

    /**
     * The hidden post must not merely be stripped from `included`: the
     * relationship linkage must be absent too, or the client is told a post
     * exists at an id it cannot fetch.
     */
    #[Test]
    public function a_hidden_first_post_is_not_linked_in_the_relationship()
    {
        $body = json_decode($this->showDiscussion(1), true);

        $this->assertNull($body['data']['relationships']['firstPost']['data'] ?? null);
    }

    #[Test]
    public function the_author_still_receives_their_own_hidden_first_post()
    {
        $this->assertStringContainsString('hidden opening post', $this->showDiscussion(1, 2));
    }

    #[Test]
    public function an_admin_still_receives_a_hidden_first_post()
    {
        $this->assertStringContainsString('hidden opening post', $this->showDiscussion(1, 1));
    }

    #[Test]
    public function a_visible_first_post_is_still_serialised()
    {
        $body = $this->showDiscussion(3);

        $this->assertStringContainsString('visible opening post', $body);

        $decoded = json_decode($body, true);
        $this->assertEquals('3', $decoded['data']['relationships']['firstPost']['data']['id'] ?? null);
    }
}
