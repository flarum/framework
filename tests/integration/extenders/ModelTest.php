<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\User\User;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class ModelTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ]
        ]);
    }

    protected function tearDown()
    {
        if (!is_null($discussion = Discussion::find(1))) {
            $discussion->delete();
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function custom_relationship_does_not_exist_by_default()
    {
        $this->prepDB();

        $user = User::find(1);

        $this->expectException(\BadMethodCallException::class);
        $user->customRelation();
    }

    /**
     * @test
     */
    public function custom_relationship_exists_if_added()
    {
        $this->extend((new Extend\Model(User::class))->relationship('customRelation', function (User $user) {
            return $user->hasMany(Discussion::class, 'user_id');
        }));

        $this->prepDB();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    /**
     * @test
     */
    public function custom_relationship_exists_and_can_return_instances_if_added()
    {
        $this->extend((new Extend\Model(User::class))->relationship('customRelation', function (User $user) {
            return $user->hasMany(Discussion::class, 'user_id');
        }));

        $this->prepDB();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1]
            ]
        ]);

        $user = User::find(1);

        $this->assertNotEquals([], $user->customRelation()->get()->toArray());
        $this->assertContains(json_encode(__CLASS__), json_encode($user->customRelation()->get()));
    }

    /**
     * @test
     */
    public function custom_relationship_does_not_exist_if_added_to_unrelated_model()
    {
        $this->extend((new Extend\Model(User::class))->relationship('customRelation', function (User $user) {
            return $user->hasMany(Discussion::class, 'user_id');
        }));

        $this->prepDB();
        $this->prepareDatabase([
            'groups' => [
                $this->adminGroup()
            ]
        ]);

        $group = Group::find(1);

        $this->expectException(\BadMethodCallException::class);
        $group->customRelation();
    }

    /**
     * @test
     */
    public function custom_default_attribute_doesnt_exist_if_not_set()
    {
        $group = new Group;

        $this->assertNotEquals('Custom Default', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_default_attribute_works_if_set()
    {
        $this->extend((new Extend\Model(Group::class))->configureDefaultAttributes(function ($defaults) {
            $defaults['name_singular'] = 'Custom Default';
            return $defaults;
        }));

        $this->app();

        $group = new Group;

        $this->assertEquals('Custom Default', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_default_attribute_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend((new Extend\Model(Group::class))->configureDefaultAttributes(function ($defaults) {
            $defaults['name_singular'] = 'Custom Default';
            return $defaults;
        }));

        $this->app();

        $user = new User;

        $this->assertNotEquals('Custom Default', $user->name_singular);
    }

    /**
     * @test
     */
    public function custom_date_doesnt_exist_if_not_set()
    {
        $post = new Post;

        $this->assertContains('hidden_at', $post->getDates());
    }

    /**
     * @test
     */
    public function custom_date_works_if_set()
    {
        $this->extend((new Extend\Model(Post::class))->configureDates(function ($dates) {
            if (($key = array_search('hidden_at', $dates)) !== false) {
                unset($dates[$key]);
            }

            return $dates;
        }));

        $this->app();

        $post = new Post;

        $this->assertNotContains('hidden_at', $post->getDates());
    }

    /**
     * @test
     */
    public function custom_date_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend((new Extend\Model(Post::class))->configureDates(function ($dates) {
            if (($key = array_search('hidden_at', $dates)) !== false) {
                unset($dates[$key]);
            }
            return $dates;
        }));

        $this->app();

        $discussion = new Discussion;

        $this->assertContains('hidden_at', $discussion->getDates());
    }
}