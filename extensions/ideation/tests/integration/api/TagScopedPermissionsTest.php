<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Voting and moving are `discussion.*` permissions, so flarum/tags can grant
 * them per restricted tag, and denies them in a restricted tag to anyone
 * granted them only globally.
 */
class TagScopedPermissionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected const VOTER = 2;
    protected const TAG_MOD = 3;
    protected const MEMBER = 4;
    protected const GLOBAL_MOD = 5;

    protected const VOTERS_GROUP = 5;
    protected const TAG_MODS_GROUP = 6;

    protected const RESTRICTED = 1;
    protected const UNTAGGED = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-ideation');

        $users = [];
        foreach ([self::VOTER => 'voter', self::TAG_MOD => 'tagmod', self::MEMBER => 'member', self::GLOBAL_MOD => 'globalmod'] as $id => $name) {
            $users[] = ['id' => $id, 'username' => $name, 'email' => "$name@machine.local", 'password' => 'too-obscure', 'is_email_confirmed' => 1];
        }

        $discussions = [];
        $posts = [];
        foreach ([self::RESTRICTED, self::UNTAGGED] as $id) {
            $discussions[] = [
                'id' => $id, 'title' => "Idea $id", 'slug' => "idea-$id", 'user_id' => self::VOTER, 'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'first_post_id' => $id, 'last_post_number' => 1,
                'ideation_status' => 'under_review', 'ideation_votes' => 0,
            ];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'user_id' => self::VOTER, 'type' => 'comment', 'content' => '<t><p>Body</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()];
        }

        $this->prepareDatabase([
            'users' => $users,
            'groups' => [
                ['id' => self::VOTERS_GROUP, 'name_singular' => 'Voter', 'name_plural' => 'Voters', 'is_hidden' => 0],
                ['id' => self::TAG_MODS_GROUP, 'name_singular' => 'Tag mod', 'name_plural' => 'Tag mods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => self::VOTER, 'group_id' => Group::MEMBER_ID],
                ['user_id' => self::VOTER, 'group_id' => self::VOTERS_GROUP],
                ['user_id' => self::TAG_MOD, 'group_id' => Group::MEMBER_ID],
                ['user_id' => self::TAG_MOD, 'group_id' => self::TAG_MODS_GROUP],
                ['user_id' => self::MEMBER, 'group_id' => Group::MEMBER_ID],
                ['user_id' => self::GLOBAL_MOD, 'group_id' => Group::MEMBER_ID],
                ['user_id' => self::GLOBAL_MOD, 'group_id' => Group::MODERATOR_ID],
            ],
            'group_permission' => [
                // Every member can read the restricted tag; only these groups
                // can vote or move ideas in it.
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => self::VOTERS_GROUP, 'permission' => 'tag1.discussion.ideationVote'],
                ['group_id' => self::TAG_MODS_GROUP, 'permission' => 'tag1.discussion.ideationModerate'],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Restricted', 'slug' => 'restricted', 'is_primary' => true, 'position' => 0, 'is_restricted' => true],
            ],
            'discussions' => $discussions,
            'posts' => $posts,
            'discussion_tag' => [
                ['discussion_id' => self::RESTRICTED, 'tag_id' => 1],
            ],
        ]);
    }

    protected function patch(int $discussionId, int $actor, array $attributes): ResponseInterface
    {
        return $this->send($this->request('PATCH', "/api/discussions/$discussionId", [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'discussions', 'id' => (string) $discussionId, 'attributes' => $attributes]],
        ]));
    }

    protected function show(int $discussionId, int $actor): array
    {
        $response = $this->send($this->request('GET', "/api/discussions/$discussionId", ['authenticatedAs' => $actor]));
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    protected function ideationStatus(int $discussionId): ?string
    {
        return $this->database()->table('discussions')->where('id', $discussionId)->value('ideation_status');
    }

    #[Test]
    public function a_member_granted_the_tag_scoped_permission_can_vote_in_a_restricted_tag()
    {
        $this->assertTrue($this->show(self::RESTRICTED, self::VOTER)['canIdeationVote']);

        $response = $this->patch(self::RESTRICTED, self::VOTER, ['ideationVoted' => true]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, (int) $this->database()->table('discussions')->where('id', self::RESTRICTED)->value('ideation_votes'));
    }

    #[Test]
    public function a_member_with_only_the_global_permission_cannot_vote_in_a_restricted_tag()
    {
        $this->assertFalse($this->show(self::RESTRICTED, self::MEMBER)['canIdeationVote']);
        $this->assertSame(403, $this->patch(self::RESTRICTED, self::MEMBER, ['ideationVoted' => true])->getStatusCode());
        $this->assertSame(0, $this->database()->table('ideation_votes')->count());

        // Outside the restricted tag, the global permission still applies.
        $this->assertSame(200, $this->patch(self::UNTAGGED, self::MEMBER, ['ideationVoted' => true])->getStatusCode());
    }

    #[Test]
    public function a_closed_idea_stays_closed_to_a_tag_scoped_voter()
    {
        $this->database()->table('discussions')->where('id', self::RESTRICTED)->update(['ideation_status' => 'shipped']);

        $this->assertSame(403, $this->patch(self::RESTRICTED, self::VOTER, ['ideationVoted' => true])->getStatusCode());
    }

    #[Test]
    public function a_moderator_granted_the_tag_scoped_permission_can_move_an_idea_in_a_restricted_tag()
    {
        $this->assertTrue($this->show(self::RESTRICTED, self::TAG_MOD)['canIdeationModerate']);

        $response = $this->patch(self::RESTRICTED, self::TAG_MOD, ['ideationStatus' => 'planned']);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('planned', $this->ideationStatus(self::RESTRICTED));

        // The scoped permission is no use outside its tag.
        $this->assertSame(403, $this->patch(self::UNTAGGED, self::TAG_MOD, ['ideationStatus' => 'planned'])->getStatusCode());
        $this->assertSame('under_review', $this->ideationStatus(self::UNTAGGED));
    }

    #[Test]
    public function a_moderator_with_only_the_global_permission_cannot_move_an_idea_in_a_restricted_tag()
    {
        $this->assertFalse($this->show(self::RESTRICTED, self::GLOBAL_MOD)['canIdeationModerate']);
        $this->assertSame(403, $this->patch(self::RESTRICTED, self::GLOBAL_MOD, ['ideationStatus' => 'planned'])->getStatusCode());
        $this->assertSame('under_review', $this->ideationStatus(self::RESTRICTED));

        $this->assertSame(200, $this->patch(self::UNTAGGED, self::GLOBAL_MOD, ['ideationStatus' => 'planned'])->getStatusCode());
    }

    #[Test]
    public function a_merge_needs_the_moderate_permission_on_the_target_as_well()
    {
        $this->database()->table('ideation_votes')->insert([
            ['discussion_id' => self::RESTRICTED, 'user_id' => self::VOTER, 'created_at' => Carbon::now()],
        ]);
        $this->database()->table('discussions')->where('id', self::RESTRICTED)->update(['ideation_votes' => 1]);

        // The tag moderator can see the untagged idea but not moderate it.
        $this->assertSame(403, $this->patch(self::RESTRICTED, self::TAG_MOD, ['ideationMergedIntoId' => self::UNTAGGED])->getStatusCode());
        // And the global moderator cannot reach into the restricted tag.
        $this->assertSame(403, $this->patch(self::UNTAGGED, self::GLOBAL_MOD, ['ideationMergedIntoId' => self::RESTRICTED])->getStatusCode());

        $this->assertSame('under_review', $this->ideationStatus(self::RESTRICTED));
        $this->assertSame('under_review', $this->ideationStatus(self::UNTAGGED));
        $this->assertSame(1, (int) $this->database()->table('discussions')->where('id', self::RESTRICTED)->value('ideation_votes'));
        $this->assertSame(0, (int) $this->database()->table('discussions')->where('id', self::UNTAGGED)->value('ideation_votes'));
        $this->assertSame(0, $this->database()->table('posts')->where('type', 'ideationStatus')->count());
    }

    #[Test]
    public function members_and_moderators_get_the_tag_scopable_permissions_by_default()
    {
        $permissions = fn (int $group) => $this->database()->table('group_permission')->where('group_id', $group)->pluck('permission')->all();

        $this->assertContains('discussion.ideationVote', $permissions(Group::MEMBER_ID));
        $this->assertContains('discussion.ideationModerate', $permissions(Group::MODERATOR_ID));
    }
}
