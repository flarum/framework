<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\authorization;

use Flarum\Group\Group;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class TagPolicyTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag6.arbitraryAbility!'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.arbitraryAbility!'],
            ]
        ]);
    }

    /**
     * @test
     */
    public function has_ability_when_allowed_in_restricted_tag()
    {
        $this->app();

        $tag = Tag::find(6);

        $this->assertTrue(User::find(2)->can('arbitraryAbility!', $tag));
    }

    /**
     * @test
     */
    public function has_ability_in_child_when_allowed_in_top_tag_and_child()
    {
        $this->app();

        $tag = Tag::find(8);

        $this->assertTrue(User::find(2)->can('arbitraryAbility!', $tag));
    }

    /**
     * @test
     */
    public function doesnt_have_ability_in_child_when_allowed_in_child_but_not_parent()
    {
        $this->app();

        $this->database()->table('group_permission')->where('permission', 'tag6.arbitraryAbility!')->delete();

        $tag = Tag::find(8);

        $this->assertFalse(User::find(2)->can('arbitraryAbility!', $tag));
    }

    /**
     * @test
     */
    public function nonrestricted_tag_falls_back_to_global_when_allowed()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'arbitraryAbility!']
            ]
        ]);

        $this->app();

        $tag = Tag::find(1);

        $this->assertTrue(User::find(2)->can('arbitraryAbility!', $tag));
    }

    /**
     * @test
     */
    public function nonrestricted_tag_falls_back_to_global_when_not_allowed()
    {
        $this->app();

        $tag = Tag::find(1);

        $this->assertFalse(User::find(2)->can('arbitraryAbility!', $tag));
    }
}
