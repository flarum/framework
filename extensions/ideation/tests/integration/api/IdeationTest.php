<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Mail\Events\MessageSent;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class IdeationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected const MEMBER = 2;
    protected const MOD = 3;
    protected const OTHER = 4;
    protected const OUTSIDER = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ideation');

        $users = [$this->normalUser()];
        foreach ([self::MOD => 'moddy', self::OTHER => 'other', self::OUTSIDER => 'outsider'] as $id => $name) {
            $users[] = ['id' => $id, 'username' => $name, 'email' => "$name@machine.local", 'password' => 'too-obscure', 'is_email_confirmed' => 1];
        }

        $discussions = [];
        $posts = [];
        foreach (range(1, 4) as $id) {
            $discussions[] = [
                'id' => $id, 'title' => "Idea $id", 'slug' => "idea-$id", 'user_id' => self::MEMBER, 'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'first_post_id' => $id, 'last_post_number' => 1,
                'ideation_status' => $id === 4 ? null : 'under_review', 'ideation_votes' => 0,
            ];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'user_id' => self::MEMBER, 'type' => 'comment', 'content' => '<t><p>Body</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()];
        }

        $this->prepareDatabase([
            'users' => $users,
            'group_user' => [
                ['user_id' => self::MEMBER, 'group_id' => 3],
                ['user_id' => self::MOD, 'group_id' => 3],
                ['user_id' => self::MOD, 'group_id' => 4],
                ['user_id' => self::OTHER, 'group_id' => 3],
                ['user_id' => self::OUTSIDER, 'group_id' => 3],
            ],
            'discussions' => $discussions,
            'posts' => $posts,
        ]);
    }

    protected function patch(int $discussionId, ?int $actor, array $attributes): ResponseInterface
    {
        return $this->send($this->request('PATCH', "/api/discussions/$discussionId", [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'discussions', 'id' => (string) $discussionId, 'attributes' => $attributes]],
        ]));
    }

    protected function attrs(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    protected function votes(int $discussionId): int
    {
        return (int) $this->database()->table('discussions')->where('id', $discussionId)->value('ideation_votes');
    }

    protected function voteRows(int $discussionId): array
    {
        return $this->database()->table('ideation_votes')->where('discussion_id', $discussionId)->orderBy('user_id')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    #[Test]
    public function posting_an_idea_starts_it_under_review_with_the_authors_vote()
    {
        $response = $this->send($this->request('POST', '/api/discussions', [
            'authenticatedAs' => self::MEMBER,
            'json' => ['data' => ['type' => 'discussions', 'attributes' => [
                'title' => 'Dark mode', 'content' => 'Please', 'ideationStatus' => 'shipped',
            ]]],
        ]));

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $attrs = $this->attrs($response);
        $this->assertSame('under_review', $attrs['ideationStatus'], 'a member cannot post an idea straight into Shipped');
        $this->assertSame(1, $attrs['ideationVotes']);
        $this->assertTrue($attrs['ideationVoted']);
    }

    #[Test]
    public function members_without_the_permission_cannot_post_ideas()
    {
        $this->database()->table('group_permission')->where('permission', 'ideation.postIdeas')->delete();

        $response = $this->send($this->request('POST', '/api/discussions', [
            'authenticatedAs' => self::MEMBER,
            'json' => ['data' => ['type' => 'discussions', 'attributes' => [
                'title' => 'Dark mode', 'content' => 'Please', 'ideationStatus' => 'under_review',
            ]]],
        ]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, $this->database()->table('discussions')->where('title', 'Dark mode')->count());
    }

    #[Test]
    public function a_vote_counts_once_and_an_unvote_takes_it_back()
    {
        foreach ([1, 2] as $attempt) {
            $response = $this->patch(1, self::OTHER, ['ideationVoted' => true]);
            $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
            $this->assertSame(1, $this->attrs($response)['ideationVotes'], "attempt $attempt");
            $this->assertTrue($this->attrs($response)['ideationVoted']);
        }

        $this->assertSame([self::OTHER], $this->voteRows(1));

        foreach ([1, 2] as $attempt) {
            $response = $this->patch(1, self::OTHER, ['ideationVoted' => false]);
            $this->assertSame(0, $this->attrs($response)['ideationVotes'], "attempt $attempt");
            $this->assertFalse($this->attrs($response)['ideationVoted']);
        }

        $this->assertSame(0, $this->votes(1));
    }

    #[Test]
    public function guests_and_closed_ideas_cannot_be_voted_on()
    {
        $this->assertContains($this->patch(1, null, ['ideationVoted' => true])->getStatusCode(), [400, 401, 403]);

        $this->database()->table('discussions')->where('id', 2)->update(['ideation_status' => 'shipped']);
        $this->assertSame(403, $this->patch(2, self::OTHER, ['ideationVoted' => true])->getStatusCode());

        // Not an idea at all.
        $this->assertSame(403, $this->patch(4, self::OTHER, ['ideationVoted' => true])->getStatusCode());

        $this->assertSame(0, $this->database()->table('ideation_votes')->count());
    }

    #[Test]
    public function only_moderators_move_ideas_and_each_move_is_posted_in_the_discussion()
    {
        $this->assertSame(403, $this->patch(1, self::MEMBER, ['ideationStatus' => 'planned'])->getStatusCode());
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationStatus' => 'merged'])->getStatusCode());

        $response = $this->patch(1, self::MOD, ['ideationStatus' => 'planned']);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('planned', $this->attrs($response)['ideationStatus']);

        $post = $this->database()->table('posts')->where('discussion_id', 1)->where('type', 'ideationStatus')->first();
        $this->assertNotNull($post);
        $this->assertSame(['under_review', 'planned', null], json_decode($post->content, true));

        // Two moves in a row by the same moderator read as one.
        $this->patch(1, self::MOD, ['ideationStatus' => 'in_progress']);
        $this->assertSame(1, $this->database()->table('posts')->where('discussion_id', 1)->where('type', 'ideationStatus')->count());

        // A moderator can put any discussion on the roadmap.
        $this->assertSame('planned', $this->attrs($this->patch(4, self::MOD, ['ideationStatus' => 'planned']))['ideationStatus']);
    }

    #[Test]
    public function shipping_tells_every_voter_who_can_see_the_idea_and_nobody_else()
    {
        foreach ([self::MEMBER, self::OTHER, self::MOD] as $voter) {
            $this->patch(1, $voter, ['ideationVoted' => true]);
        }

        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationStatus' => 'shipped'])->getStatusCode());

        $notified = $this->database()->table('notifications')->where('type', 'ideationShipped')->orderBy('user_id')->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        // The moderator who shipped it is not told what they just did, and
        // OUTSIDER never voted.
        $this->assertSame([self::MEMBER, self::OTHER], $notified);

        // Shipping it again is not a second notification.
        $this->patch(1, self::MOD, ['ideationStatus' => 'in_progress']);
        $this->patch(1, self::MOD, ['ideationStatus' => 'shipped']);
        $this->assertSame(2, $this->database()->table('notifications')->where('type', 'ideationShipped')->count());
    }

    #[Test]
    public function a_voter_who_cannot_see_the_idea_is_not_told_it_shipped()
    {
        $this->patch(1, self::OTHER, ['ideationVoted' => true]);
        $this->database()->table('discussions')->where('id', 1)->update(['hidden_at' => Carbon::now(), 'hidden_user_id' => self::MOD]);

        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationStatus' => 'shipped'])->getStatusCode());

        $this->assertSame(0, $this->database()->table('notifications')->where('type', 'ideationShipped')->count());
    }

    #[Test]
    public function merging_carries_votes_over_without_counting_anyone_twice()
    {
        $this->patch(1, self::MEMBER, ['ideationVoted' => true]);
        $this->patch(1, self::OTHER, ['ideationVoted' => true]);
        $this->patch(2, self::OTHER, ['ideationVoted' => true]);
        $this->patch(2, self::OUTSIDER, ['ideationVoted' => true]);

        $this->assertSame(403, $this->patch(1, self::MEMBER, ['ideationMergedIntoId' => 2])->getStatusCode());
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationMergedIntoId' => 1])->getStatusCode());
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationMergedIntoId' => 4])->getStatusCode(), 'not an idea');

        $response = $this->patch(1, self::MOD, ['ideationMergedIntoId' => 2]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('merged', $this->attrs($response)['ideationStatus']);
        $this->assertSame(2, $this->attrs($response)['ideationMergedIntoId']);

        // The event post names the other idea by id only, never by title.
        $merged = $this->database()->table('posts')->where('discussion_id', 1)->where('type', 'ideationStatus')->value('content');
        $this->assertSame(['under_review', 'merged', 2], json_decode($merged, true));

        $this->assertSame([self::MEMBER, self::OTHER, self::OUTSIDER], $this->voteRows(2));
        $this->assertSame(3, $this->votes(2));
        $this->assertSame([], $this->voteRows(1));
        $this->assertSame(0, $this->votes(1));

        // An idea merged into 3 brings 1 along with it.
        $this->assertSame(200, $this->patch(2, self::MOD, ['ideationMergedIntoId' => 3])->getStatusCode());
        $this->assertSame(3, (int) $this->database()->table('discussions')->where('id', 1)->value('ideation_merged_into_id'));
        $this->assertSame(3, $this->votes(3));
    }

    #[Test]
    public function the_board_filters_by_status_and_sorts_by_votes()
    {
        $this->database()->table('discussions')->where('id', 1)->update(['ideation_status' => 'planned', 'ideation_votes' => 5]);
        $this->database()->table('discussions')->where('id', 2)->update(['ideation_status' => 'planned', 'ideation_votes' => 9]);
        $this->database()->table('discussions')->where('id', 3)->update(['ideation_status' => 'merged']);

        $ids = fn (array $query) => array_column(json_decode((string) $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => self::OTHER])->withQueryParams($query)
        )->getBody(), true)['data'], 'id');

        $this->assertSame(['2', '1'], $ids(['filter' => ['ideation' => 'planned'], 'sort' => '-ideationVotes']));
        $this->assertSame(['1', '2'], $ids(['filter' => ['ideation' => 'planned'], 'sort' => 'ideationVotes']));
        $this->assertSame(['2', '1'], $ids(['filter' => ['ideation' => 'all'], 'sort' => '-ideationVotes']), 'merged ideas are off the board');
        $this->assertSame([], $ids(['filter' => ['ideation' => 'merged']]));
        $this->assertSame([], $ids(['filter' => ['ideation' => "planned' OR 1=1 --"]]));
    }

    #[Test]
    public function the_voted_flag_costs_one_query_however_many_ideas_are_listed()
    {
        $rows = [];
        $posts = [];
        foreach (range(10, 40) as $id) {
            $rows[] = ['id' => $id, 'title' => "Idea $id", 'slug' => "idea-$id", 'user_id' => self::MEMBER, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'first_post_id' => $id, 'last_post_number' => 1, 'ideation_status' => 'planned', 'ideation_votes' => 0];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'user_id' => self::MEMBER, 'type' => 'comment', 'content' => '<t><p>Body</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()];
        }
        // Discussions first without their first post, then the posts, then
        // the link: PostgreSQL checks both foreign keys on every insert.
        $this->database()->table('discussions')->insert(array_map(fn ($row) => ['first_post_id' => null] + $row, $rows));
        $this->database()->table('posts')->insert($posts);
        foreach ($rows as $row) {
            $this->database()->table('discussions')->where('id', $row['id'])->update(['first_post_id' => $row['first_post_id']]);
        }
        $this->database()->table('ideation_votes')->insert(['discussion_id' => 40, 'user_id' => self::OTHER, 'created_at' => Carbon::now()]);

        $count = function (int $limit, ?int $actor): array {
            $db = $this->app()->getContainer()->make('db');
            $db->enableQueryLog();
            $db->flushQueryLog();

            $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => $actor])
                ->withQueryParams(['filter' => ['ideation' => 'planned'], 'page' => ['limit' => $limit]]));
            $this->assertSame(200, $response->getStatusCode());

            $queries = array_column($db->getQueryLog(), 'query');
            $db->disableQueryLog();

            return [
                'total' => count($queries),
                'votes' => count(array_filter($queries, fn ($q) => str_contains($q, 'ideation_votes"') || str_contains($q, 'ideation_votes`'))),
                'body' => json_decode((string) $response->getBody(), true),
            ];
        };

        // The first request of a test also stamps last_seen_at and warms the
        // permission and asset caches; measure the ones after it.
        $count(1, self::OTHER);

        $two = $count(2, self::OTHER);
        $twenty = $count(20, self::OTHER);

        $this->assertSame(1, $two['votes']);
        $this->assertSame(1, $twenty['votes']);
        $this->assertSame($two['total'], $twenty['total']);

        $voted = array_column(array_map(fn ($d) => ['id' => $d['id'], 'v' => $d['attributes']['ideationVoted']], $twenty['body']['data']), 'v', 'id');
        $this->assertTrue($voted['40']);
        $this->assertFalse($voted['39']);

        $this->assertSame(0, $count(20, null)['votes'], 'a guest costs no vote query');
    }

    #[Test]
    public function deleting_a_member_takes_their_votes_off_the_counts()
    {
        $this->patch(1, self::OTHER, ['ideationVoted' => true]);
        $this->patch(2, self::OTHER, ['ideationVoted' => true]);
        $this->patch(2, self::MEMBER, ['ideationVoted' => true]);

        $response = $this->send($this->request('DELETE', '/api/users/'.self::OTHER, ['authenticatedAs' => 1]));
        $this->assertSame(204, $response->getStatusCode());

        $this->assertSame(0, $this->votes(1));
        $this->assertSame(1, $this->votes(2));
    }

    #[Test]
    public function shipping_again_after_a_move_back_never_alerts_or_emails_a_voter_twice()
    {
        $emails = [];
        $this->extend((new Extend\Event())->listen(MessageSent::class, function (MessageSent $event) use (&$emails) {
            foreach ($event->message->getTo() as $address) {
                $emails[] = $address->getAddress();
            }
        }));

        $this->prepareDatabase([
            'users' => [['id' => 6, 'username' => 'moddy2', 'email' => 'moddy2@machine.local', 'password' => 'too-obscure', 'is_email_confirmed' => 1]],
            'group_user' => [['user_id' => 6, 'group_id' => 4]],
        ]);

        foreach ([self::MEMBER, self::OTHER] as $voter) {
            $this->patch(1, $voter, ['ideationVoted' => true]);
        }

        $alerts = fn () => $this->database()->table('notifications')->where('type', 'ideationShipped')->orderBy('user_id')->get(['user_id', 'is_deleted'])
            ->map(fn ($n) => [(int) $n->user_id, (bool) $n->is_deleted])->all();

        // One moderator ships it.
        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationStatus' => 'shipped'])->getStatusCode());
        $this->assertSame([[self::MEMBER, false], [self::OTHER, false]], $alerts());

        // Moved back: the alert is taken back.
        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationStatus' => 'in_progress'])->getStatusCode());
        $this->assertSame([[self::MEMBER, true], [self::OTHER, true]], $alerts());

        // Another moderator ships it again: the same alerts come back, and
        // nobody gets a second one or a second email.
        $this->assertSame(200, $this->patch(1, 6, ['ideationStatus' => 'shipped'])->getStatusCode());
        $this->assertSame([[self::MEMBER, false], [self::OTHER, false]], $alerts());

        sort($emails);
        $this->assertSame(['normal@machine.local', 'other@machine.local'], $emails);
    }

    #[Test]
    public function an_idea_can_only_be_merged_into_an_open_one()
    {
        $this->patch(2, self::OTHER, ['ideationVoted' => true]);

        foreach (['shipped', 'declined'] as $closed) {
            $this->database()->table('discussions')->where('id', 2)->update(['ideation_status' => $closed]);

            $this->assertSame(422, $this->patch(1, self::MOD, ['ideationMergedIntoId' => 2])->getStatusCode());
            $this->assertSame('under_review', $this->database()->table('discussions')->where('id', 1)->value('ideation_status'));
            $this->assertSame(1, $this->votes(2));
        }

        $this->database()->table('discussions')->where('id', 2)->update(['ideation_status' => 'planned']);
        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationMergedIntoId' => 2])->getStatusCode());
    }

    #[Test]
    public function a_negated_status_filter_keeps_discussions_that_are_not_ideas()
    {
        $this->database()->table('discussions')->where('id', 2)->update(['ideation_status' => 'planned']);

        $ids = function (array $filter) {
            $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => self::MEMBER])->withQueryParams(['filter' => $filter]));
            $ids = array_map(fn ($d) => (int) $d['id'], json_decode((string) $response->getBody(), true)['data']);
            sort($ids);

            return $ids;
        };

        $this->assertSame([2], $ids(['ideation' => 'planned']));
        $this->assertSame([1, 3, 4], $ids(['-ideation' => 'planned']));
        $this->assertSame([4], $ids(['-ideation' => 'all']));
    }

    #[Test]
    public function a_member_delete_that_fails_leaves_the_counts_alone()
    {
        $this->patch(1, self::OTHER, ['ideationVoted' => true]);
        $this->patch(2, self::OTHER, ['ideationVoted' => true]);

        // The row delete fails after the API's Deleting event has gone out.
        User::deleting(function (User $user) {
            if ($user->id === self::OTHER) {
                throw new RuntimeException('The database went away.');
            }
        });

        $response = $this->send($this->request('DELETE', '/api/users/'.self::OTHER, ['authenticatedAs' => 1]));
        $this->assertSame(500, $response->getStatusCode());

        $this->assertSame(1, $this->votes(1));
        $this->assertSame(1, $this->votes(2));
        $this->assertSame([self::OTHER], $this->voteRows(1));
    }
}
