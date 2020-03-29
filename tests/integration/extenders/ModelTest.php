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
}