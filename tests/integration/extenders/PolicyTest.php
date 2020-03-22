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
use Flarum\User\AbstractPolicy;
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
    protected $hideQuery = ['json' => ['data' => ['attributes' => ['isHidden' => true]]]];

    private function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Hidden Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0, 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 2, 'title' => 'Unrelated Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
            ]
        ]);
    }

    /**
     * @test
     */
    public function guest_cant_see_hidden_discussions_by_default()
    {
        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_can_see_hidden_discussions_if_allowed()
    {
        $this->extend(
            (new Extend\Policy())
                ->add(CustomPolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_cant_hide_discussion_by_default()
    {
        $this->prepDb();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('PATCH', '/api/discussions/2', $this->hideQuery), 2
            )
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unrelated_user_can_hide_discussion_if_allowed()
    {
        $this->extend(
            (new Extend\Policy())
                ->add(CustomPolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('PATCH', '/api/discussions/2', $this->hideQuery), 2
            )
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
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('PATCH', '/api/discussions/2', $this->hideQuery), 2
            )
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
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
                ->add(ForceAllowHidePolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('PATCH', '/api/discussions/2', $this->hideQuery), 2
            )
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
            (new Extend\Policy())
                ->add(CustomPolicy::class)
                ->add(DenyHidePolicy::class)
                ->add(ForceAllowHidePolicy::class)
                ->add(ForceDenyHidePolicy::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('PATCH', '/api/discussions/2', $this->hideQuery), 2
            )
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}

class CustomPolicy extends AbstractPolicy
{
    protected $model = Discussion::class;

    protected function hide(User $user, Discussion $discussion)
    {
        return $this->allow();
    }

    protected function scopeQuery(User $actor, Builder $query)
    {
        $query->orWhere('user_id', '!=', -1);
    }
}

class DenyHidePolicy extends AbstractPolicy
{
    protected $model = Discussion::class;

    protected function hide(User $user, Discussion $discussion)
    {
        return $this->deny();
    }
}

class ForceAllowHidePolicy extends AbstractPolicy
{
    protected $model = Discussion::class;

    protected function hide(User $user, Discussion $discussion)
    {
        return $this->forceAllow();
    }
}

class ForceDenyHidePolicy extends AbstractPolicy
{
    protected $model = Discussion::class;

    protected function hide(User $user, Discussion $discussion)
    {
        return $this->forceDeny();
    }
}
