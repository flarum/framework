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
use Flarum\User\Access\AbstractPolicy;
use Flarum\Extend;
use Flarum\Tests\integration\TestCase;
use Flarum\Tests\integration\BuildsHttpRequests;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class PolicyTest extends TestCase
{
    use BuildsHttpRequests;
    use RetrievesAuthorizedUsers;

    // Request body to hide discussions sent in tests.
    protected $hideQuery = ['authenticatedAs' => 2, 'json' => ['data' => ['attributes' => ['isHidden' => true]]]];

    private function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
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
        $this->prepDb();

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
            (new Extend\Policy(Discussion::class))
                ->add(CustomPolicy::class)
        );

        $this->prepDb();

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
            (new Extend\Policy(Discussion::class))
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
        );

        $this->prepDb();

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
            (new Extend\Policy(Discussion::class))
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
                ->add(ForceAllowHidePolicy::class)
        );

        $this->prepDb();

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
        // Because the force deny policy is last, this also shows that
        // order isn't considered, as the last result overrides all.
        $this->extend(
            (new Extend\Policy(Discussion::class))
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
                ->add(ForceAllowHidePolicy::class)
                ->add(ForceDenyHidePolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', $this->hideQuery)
        );

        $this->assertEquals(403, $response->getStatusCode());
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
