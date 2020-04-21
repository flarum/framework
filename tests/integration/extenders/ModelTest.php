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
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class ModelTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'discussions' => []
        ]);
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

        $this->app();

        $this->assertNotEquals('Custom Default', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_default_attribute_works_if_set()
    {
        $this->extend((new Extend\Model(Group::class))->default('name_singular', 'Custom Default'));

        $this->app();

        $group = new Group;

        $this->assertEquals('Custom Default', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_default_attribute_evaluated_at_runtime_if_callable()
    {
        $time = Carbon::now();
        $this->extend((new Extend\Model(Group::class))->default('name_singular', function () {
            return Carbon::now();
        }));

        $this->app();

        sleep(2);

        $group = new Group;

        $this->assertGreaterThanOrEqual($time->diffInSeconds($group->name_singular), 2);
    }

    /**
     * @test
     */
    public function custom_default_attribute_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend((new Extend\Model(Group::class))->default('name_singular', 'Custom Default'));

        $this->app();

        $user = new User;

        $this->assertNotEquals('Custom Default', $user->name_singular);
    }

    /**
     * @test
     */
    public function custom_date_attribute_doesnt_exist_by_default()
    {
        $post = new Post;

        $this->app();

        $this->assertNotContains('custom', $post->getDates());
    }

    /**
     * @test
     */
    public function custom_date_attribute_can_be_set()
    {
        $this->extend((new Extend\Model(Post::class))->dateAttribute('custom'));

        $this->app();

        $post = new Post;

        $this->assertContains('custom', $post->getDates());
    }

    /**
     * @test
     */
    public function custom_date_attribute_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend((new Extend\Model(Post::class))->dateAttribute('custom'));

        $this->app();

        $discussion = new Discussion;

        $this->assertNotContains('custom', $discussion->getDates());
    }
}
