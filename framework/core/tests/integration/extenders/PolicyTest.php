<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\BuildsHttpRequests;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class PolicyTest extends TestCase
{
    use BuildsHttpRequests;
    use RetrievesAuthorizedUsers;

    // Request body to hide discussions sent in tests.
    protected $hideQuery = ['authenticatedAs' => 2, 'json' => ['data' => ['attributes' => ['isHidden' => true]]]];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Unrelated Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>a normal reply - too-obscure</p></t>'],
            ]
        ]);
    }

    /**
     * @test
     */
    public function unrelated_user_cant_hide_discussion_by_default()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_can_hide_discussion_if_allowed()
    {
        $this->extend(
            (new Extend\Policy())
                ->modelPolicy(Discussion::class, CustomPolicy::class)
        );

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_cant_hide_discussion_if_denied()
    {
        $this->extend(
            (new Extend\Policy())
                ->modelPolicy(Discussion::class, DenyHidePolicy::class)
                ->modelPolicy(Discussion::class, CustomPolicy::class)
        );

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_can_hide_discussion_if_force_allowed()
    {
        $this->extend(
            (new Extend\Policy())
                ->modelPolicy(Discussion::class, ForceAllowHidePolicy::class)
                ->modelPolicy(Discussion::class, DenyHidePolicy::class)
                ->modelPolicy(Discussion::class, CustomPolicy::class)
        );

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_cant_hide_discussion_if_force_denied()
    {
        $this->extend(
            (new Extend\Policy())
                ->modelPolicy(Discussion::class, DenyHidePolicy::class)
                ->modelPolicy(Discussion::class, ForceDenyHidePolicy::class)
                ->modelPolicy(Discussion::class, CustomPolicy::class)
                ->modelPolicy(Discussion::class, ForceAllowHidePolicy::class)
        );

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function regular_user_can_start_discussions_by_default()
    {
        $this->app();

        $user = User::find(2);

        $this->assertEquals(true, $user->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function regular_user_cant_start_discussions_if_blocked_by_global_policy()
    {
        $this->extend(
            (new Extend\Policy)
            ->globalPolicy(GlobalStartDiscussionPolicy::class)
        );

        $this->app();

        $user = User::find(2);

        $this->assertEquals(false, $user->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function global_policy_doesnt_apply_if_argument_provided()
    {
        $this->extend(
            (new Extend\Policy)
                ->globalPolicy(GlobalStartDiscussionPolicy::class)
        );

        $this->app();

        $user = User::find(2);

        $this->assertEquals(true, $user->can('startDiscussion', Discussion::find(1)));
    }

    /**
     * @test
     */
    public function unrelated_user_cant_hide_post_by_default()
    {
        $this->app();

        $user = User::find(2);

        $this->assertEquals(false, $user->can('hide', Post::find(1)));
    }

    /**
     * @test
     */
    public function unrelated_user_can_hide_post_if_allowed()
    {
        $this->extend(
            (new Extend\Policy)->modelPolicy(CommentPost::class, CommentPostChildClassPolicy::class)
        );
        $this->app();

        $user = User::find(2);

        $this->assertEquals(true, $user->can('hide', Post::find(1)));
    }

    /**
     * @test
     */
    public function policies_are_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Policy)->modelPolicy(Post::class, PostParentClassPolicy::class),
            (new Extend\Policy)->modelPolicy(CommentPost::class, CommentPostChildClassPolicy::class)
        );
        $this->app();

        $user = User::find(2);

        $this->assertEquals(false, $user->can('hide', Post::find(1)));
    }
}

class CustomPolicy extends AbstractPolicy
{
    protected function hide(User $user, Discussion $discussion)
    {
        return $this->allow();
    }
}

class DenyHidePolicy extends AbstractPolicy
{
    protected function hide(User $user, Discussion $discussion)
    {
        return $this->deny();
    }
}

class ForceAllowHidePolicy extends AbstractPolicy
{
    protected function hide(User $user, Discussion $discussion)
    {
        return $this->forceAllow();
    }
}

class ForceDenyHidePolicy extends AbstractPolicy
{
    protected function hide(User $user, Discussion $discussion)
    {
        return $this->forceDeny();
    }
}

class GlobalStartDiscussionPolicy extends AbstractPolicy
{
    protected function startDiscussion(User $user)
    {
        return $this->deny();
    }
}

class PostParentClassPolicy extends AbstractPolicy
{
    protected function hide(User $user, Post $post)
    {
        return $this->deny();
    }
}

class CommentPostChildClassPolicy extends AbstractPolicy
{
    protected function hide(User $user, CommentPost $post)
    {
        return $this->allow();
    }
}
