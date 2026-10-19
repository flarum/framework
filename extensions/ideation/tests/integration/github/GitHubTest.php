<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\integration\github;

use Carbon\Carbon;
use Flarum\Ideation\GitHub\Client;
use Flarum\Ideation\Tests\integration\FakeGitHub;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class GitHubTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const MEMBER = 2;
    private const MOD = 3;
    private const SECRET = 'test-webhook-secret';

    private FakeGitHub $github;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ideation');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => self::MOD, 'username' => 'moddy', 'email' => 'moddy@machine.local', 'password' => 'too-obscure', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => self::MEMBER, 'group_id' => 3],
                ['user_id' => self::MOD, 'group_id' => 4],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Idea', 'slug' => 'idea', 'user_id' => self::MEMBER, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'first_post_id' => 1, 'last_post_number' => 1, 'ideation_status' => 'planned', 'ideation_votes' => 1],
                ['id' => 2, 'title' => 'Other idea', 'slug' => 'other', 'user_id' => self::MEMBER, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'first_post_id' => 2, 'last_post_number' => 1, 'ideation_status' => 'under_review', 'ideation_votes' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => self::MEMBER, 'type' => 'comment', 'content' => '<t><p>Body</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => self::MEMBER, 'type' => 'comment', 'content' => '<t><p>Body</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'ideation_votes' => [
                ['discussion_id' => 1, 'user_id' => self::MEMBER, 'created_at' => Carbon::now()],
            ],
        ]);

        // Through setting(), not prepareDatabase: settings are read during
        // boot, before the seeded rows are written.
        $this->setting('flarum-ideation.github.enabled', '1');
        $this->setting('flarum-ideation.github.repos', "flarum/framework\nfof/*");
        $this->setting('flarum-ideation.github.secret', self::SECRET);

        $this->github = new FakeGitHub();
        $this->github->items = [
            'flarum/framework#5034' => ['type' => 'pull', 'title' => 'Replace the Extension Manager', 'state' => 'open', 'open_issues' => null, 'closed_issues' => null, 'updated_at' => '2026-10-18T10:00:00Z'],
            'flarum/framework#4000' => ['type' => 'issue', 'title' => 'Dark mode', 'state' => 'open', 'open_issues' => null, 'closed_issues' => null, 'updated_at' => '2026-10-18T10:00:00Z'],
            'flarum/framework/milestone/12' => ['type' => 'milestone', 'title' => '2.1', 'state' => 'open', 'open_issues' => 3, 'closed_issues' => 7, 'updated_at' => '2026-10-18T10:00:00Z'],
            'fof/upload#7' => 'down',
        ];
    }

    private function boot(array $settings = []): void
    {
        foreach ($settings as $key => $value) {
            $this->setting("flarum-ideation.github.$key", $value);
        }

        $this->app()->getContainer()->instance(Client::class, $this->github);
    }

    private function patch(int $id, ?int $actor, array $attributes): ResponseInterface
    {
        return $this->send($this->request('PATCH', "/api/discussions/$id", [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'discussions', 'id' => (string) $id, 'attributes' => $attributes]],
        ]));
    }

    private function show(int $id, ?int $actor = null): array
    {
        return json_decode((string) $this->send($this->request('GET', "/api/discussions/$id", ['authenticatedAs' => $actor]))->getBody(), true)['data']['attributes'];
    }

    private function webhook(string $event, array $payload, ?string $secret = self::SECRET): ResponseInterface
    {
        $raw = json_encode($payload);
        $request = (new ServerRequest([], [], '/api/ideation/github/webhook', 'POST'))
            ->withBody((new StreamFactory())->createStream($raw))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-GitHub-Event', $event);

        if ($secret !== null) {
            $request = $request->withHeader('X-Hub-Signature-256', 'sha256='.hash_hmac('sha256', $raw, $secret));
        }

        return $this->send($request);
    }

    #[Test]
    public function moderators_link_items_from_allowed_repositories_only()
    {
        $this->boot();

        $this->assertSame(403, $this->patch(1, self::MEMBER, ['ideationGithubLink' => 'flarum/framework#5034'])->getStatusCode());
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationGithubLink' => 'https://example.com/x'])->getStatusCode());
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationGithubLink' => 'someone/private#1'])->getStatusCode(), 'not on the list');
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationGithubLink' => 'flarum/framework#9999'])->getStatusCode(), 'GitHub has no such item');
        $this->assertSame(422, $this->patch(1, self::MOD, ['ideationGithubLink' => 'fof/upload#7'])->getStatusCode(), 'GitHub is down');
        $this->assertNotContains('someone/private#1', $this->github->asked, 'a repository off the list is never even asked about');

        foreach (['https://github.com/flarum/framework/pull/5034', 'flarum/framework#4000', 'https://github.com/flarum/framework/milestone/12', 'flarum/framework#4000'] as $link) {
            $r = $this->patch(1, self::MOD, ['ideationGithubLink' => $link]);
            $this->assertSame(200, $r->getStatusCode(), $link.substr((string) $r->getBody(), 0, 900));
        }

        $links = $this->show(1, self::MEMBER)['ideationGithub'];
        $this->assertCount(3, $links, 'linking the same item twice keeps one link');
        $this->assertSame(['issue', 'milestone', 'pull'], array_column($links, 'type'));
        $this->assertSame('https://github.com/flarum/framework/pull/5034', $links[2]['url']);
        $this->assertSame(7, $links[1]['closedIssues']);

        $this->assertSame(200, $this->patch(1, self::MOD, ['ideationGithubUnlink' => $links[0]['id']])->getStatusCode());
        $this->assertCount(2, $this->show(1)['ideationGithub']);

        // Another idea's link id unlinks nothing here.
        $this->patch(1, self::MOD, ['ideationGithubUnlink' => 99999]);
        $this->assertCount(2, $this->show(1)['ideationGithub']);
    }

    #[Test]
    public function nothing_shows_or_links_while_the_integration_is_off()
    {
        $this->boot(['enabled' => '0']);

        $this->assertArrayNotHasKey('ideationGithub', $this->show(1));
        $this->assertSame(403, $this->patch(1, self::MOD, ['ideationGithubLink' => 'flarum/framework#5034'])->getStatusCode());
        $this->assertSame(404, $this->webhook('ping', ['zen' => 'hi'])->getStatusCode());
        $this->assertSame([], $this->github->asked);
    }

    #[Test]
    public function the_webhook_refuses_anything_unsigned_and_updates_linked_items()
    {
        $this->boot();
        $this->patch(1, self::MOD, ['ideationGithubLink' => 'flarum/framework#5034']);

        $merged = ['repository' => ['full_name' => 'flarum/framework'], 'pull_request' => ['number' => 5034, 'title' => 'Replace the Extension Manager', 'state' => 'closed', 'merged' => true, 'updated_at' => '2026-10-18T12:00:00Z']];

        $this->assertSame(401, $this->webhook('pull_request', $merged, null)->getStatusCode());
        $this->assertSame(401, $this->webhook('pull_request', $merged, 'wrong-secret')->getStatusCode());
        $this->assertSame('open', $this->show(1)['ideationGithub'][0]['state']);

        $response = $this->webhook('pull_request', $merged);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, json_decode((string) $response->getBody(), true)['updated']);
        $this->assertSame('merged', $this->show(1)['ideationGithub'][0]['state']);

        // A late, older delivery never undoes a newer state.
        $stale = $merged;
        $stale['pull_request'] = ['number' => 5034, 'title' => 'x', 'state' => 'open', 'updated_at' => '2026-10-18T11:00:00Z'];
        $this->webhook('pull_request', $stale);
        $this->assertSame('merged', $this->show(1)['ideationGithub'][0]['state']);

        // Without the auto rules, the idea itself stays where a moderator put it.
        $this->assertSame('planned', $this->show(1)['ideationStatus']);
    }

    #[Test]
    public function with_auto_rules_on_an_open_pull_request_moves_an_idea_along_and_finishing_ships_it()
    {
        $this->boot(['auto_progress' => '1', 'auto_ship' => '1']);

        $this->patch(1, self::MOD, ['ideationGithubLink' => 'flarum/framework#5034']);
        $this->assertSame('in_progress', $this->show(1)['ideationStatus']);

        $this->webhook('pull_request', ['repository' => ['full_name' => 'flarum/framework'], 'pull_request' => ['number' => 5034, 'title' => 'Replace the Extension Manager', 'state' => 'closed', 'merged' => true, 'updated_at' => '2026-10-18T12:00:00Z']]);
        $this->assertSame('shipped', $this->show(1)['ideationStatus']);

        $post = $this->database()->table('posts')->where('discussion_id', 1)->where('type', 'ideationStatus')->orderByDesc('id')->value('content');
        $this->assertSame('flarum/framework#5034', json_decode($post, true)[3]);

        // Nobody clicked, so every voter hears, the member who voted included.
        $this->assertSame([self::MEMBER], $this->database()->table('notifications')->where('type', 'ideationShipped')->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        // A pull request closed without merging does not count either way.
        $this->patch(2, self::MOD, ['ideationGithubLink' => 'flarum/framework#4000']);
        $this->webhook('issues', ['repository' => ['full_name' => 'flarum/framework'], 'issue' => ['number' => 4000, 'title' => 'Dark mode', 'state' => 'closed', 'state_reason' => 'not_planned', 'updated_at' => '2026-10-18T12:00:00Z']]);
        $this->assertSame('under_review', $this->show(2)['ideationStatus']);
    }

    #[Test]
    public function the_sync_refreshes_what_the_webhook_missed()
    {
        $this->boot();
        $this->patch(1, self::MOD, ['ideationGithubLink' => 'https://github.com/flarum/framework/milestone/12']);
        $this->database()->table('ideation_github_links')->update(['synced_at' => Carbon::now()->subDay()]);

        $this->github->items['flarum/framework/milestone/12'] = ['type' => 'milestone', 'title' => '2.1', 'state' => 'closed', 'open_issues' => 0, 'closed_issues' => 10, 'updated_at' => '2026-10-18T13:00:00Z'];

        $this->assertSame(1, $this->app()->getContainer()->make(\Flarum\Ideation\GitHub\Links::class)->sync());
        $link = $this->show(1)['ideationGithub'][0];
        $this->assertSame(['closed', 10], [$link['state'], $link['closedIssues']]);
    }

    #[Test]
    public function the_links_of_a_whole_page_cost_one_query()
    {
        $this->boot();
        $this->patch(1, self::MOD, ['ideationGithubLink' => 'flarum/framework#5034']);
        $this->patch(2, self::MOD, ['ideationGithubLink' => 'flarum/framework#4000']);

        $db = $this->app()->getContainer()->make('db');
        $db->enableQueryLog();
        $db->flushQueryLog();
        $body = json_decode((string) $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => self::MEMBER])->withQueryParams(['filter' => ['ideation' => 'all']]))->getBody(), true);
        $queries = array_filter(array_column($db->getQueryLog(), 'query'), fn ($q) => str_contains($q, 'ideation_github_links'));

        $this->assertCount(1, $queries);
        $this->assertSame([1, 1], array_map(fn ($d) => count($d['attributes']['ideationGithub']), $body['data']));
    }
}
