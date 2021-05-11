<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\authorization;

use Flarum\Group\Group;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class GlobalPolicyTest extends TestCase
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
            ]
        ]);
    }

    /**
     * @test
     */
    public function cant_start_discussion_globally_if_permission_not_granted()
    {
        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertFalse(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function can_start_discussion_globally_if_allowed_in_primary_tag()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag6.startDiscussion'],
            ]
        ]);

        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertTrue(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function cant_start_discussion_globally_if_allowed_in_child_tag_only()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.startDiscussion'],
            ]
        ]);

        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertFalse(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function cant_start_discussion_globally_if_allowed_in_secondary_tag()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.startDiscussion'],
            ]
        ]);

        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertFalse(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function can_start_discussion_globally_if_allowed_in_secondary_tag_and_minimums_adjusted()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.startDiscussion'],
            ]
        ]);

        $this->setting('flarum-tags.min_primary_tags', 0);
        $this->setting('flarum-tags.min_secondary_tags', 1);

        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertTrue(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function cant_start_discussion_globally_if_permission_in_insufficient_tags_requires_start_discussion_regardless_of_bypass()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'bypassTagCounts'],
            ]
        ]);

        $this->database()->table('group_permission')->where('permission', 'startDiscussion')->delete();

        $this->assertFalse(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function can_start_discussion_globally_if_start_discussion_and_bypass_allows_regardless_of_tag_count()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'bypassTagCounts'],
            ]
        ]);

        $this->app();

        $this->assertTrue(User::find(2)->can('startDiscussion'));
    }

    /**
     * @test
     */
    public function can_start_discussion_globally_if_sufficient_tags_and_allows_regardless_of_start_discussion_and_bypass()
    {
        $this->database()->table('group_permission')->where('permission', 'bypassTagCounts')->delete();

        $this->setting('flarum-tags.min_primary_tags', 0);
        $this->setting('flarum-tags.min_secondary_tags', 1);

        $this->assertTrue(User::find(2)->can('startDiscussion'));
    }
}
