<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\flags;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend\ModelVisibility;
use Flarum\Flags\Event\UserFlagCreated;
use Flarum\Flags\Extend\UserFlags;
use Flarum\Flags\Flag;
use Flarum\Flags\UserFlagger;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;

class UserFlagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-flags');
        $users = [$this->normalUser()];
        foreach ([3, 4, 5, 6] as $id) {
            $users[] = ['id' => $id, 'username' => 'testuser'.$id, 'email' => 'user'.$id.'@machine.local', 'is_email_confirmed' => 1];
        }
        $this->prepareDatabase([
            User::class => $users,
            Group::class => [['id' => 6, 'name_singular' => 'Post Moderator', 'name_plural' => 'Post Moderators']],
            'group_user' => [
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 4],
                ['group_id' => 6, 'user_id' => 5],
            ],
            'group_permission' => [['group_id' => 6, 'permission' => 'discussion.viewFlags']],
            Discussion::class => [['id' => 1, 'title' => 'Existing post', 'user_id' => 3, 'comment_count' => 1]],
            Post::class => [['id' => 1, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Existing post</p></t>']],
            'flags' => [
                ['id' => 1, 'post_id' => 1, 'target_user_id' => null, 'type' => 'user', 'user_id' => 2, 'reason' => 'spam', 'created_at' => Carbon::now()->subMinutes(5)],
                ['id' => 2, 'post_id' => null, 'target_user_id' => 3, 'type' => 'user', 'user_id' => 2, 'reason' => 'spam', 'created_at' => Carbon::now()->subMinutes(4)],
                ['id' => 3, 'post_id' => null, 'target_user_id' => 3, 'type' => 'user', 'user_id' => 4, 'reason' => 'spam', 'created_at' => Carbon::now()->subMinutes(3)],
                ['id' => 4, 'post_id' => null, 'target_user_id' => 2, 'type' => 'user', 'user_id' => 4, 'reason' => 'spam', 'created_at' => Carbon::now()->subMinutes(2)],
            ],
        ]);
    }

    private function payload(int $target = 6): array
    {
        return ['data' => [
            'type' => 'flags',
            'attributes' => ['reason' => 'spam'],
            'relationships' => ['targetUser' => ['data' => ['type' => 'users', 'id' => (string) $target]]],
        ]];
    }

    private function create(array $payload, ?int $actor = 2)
    {
        return $this->send($this->request('POST', '/api/flags', [
            'authenticatedAs' => $actor,
            'json' => $payload,
        ])->withAttribute('bypassCsrfToken', true));
    }

    #[Test]
    public function member_can_flag_an_account_and_duplicate_submission_reuses_record(): void
    {
        $response = $this->create($this->payload());
        $body = $response->getBody()->getContents();
        $this->assertSame(201, $response->getStatusCode(), $body);
        $flag = json_decode($body, true)['data'];
        $this->assertSame('6', $flag['relationships']['targetUser']['data']['id']);
        $this->assertSame('2', $flag['relationships']['user']['data']['id']);
        $this->assertNull($flag['relationships']['post']['data']);
        $second = $this->create($this->payload());
        $this->assertSame(201, $second->getStatusCode(), $second->getBody()->getContents());
        $this->assertSame(1, Flag::where('target_user_id', 6)->where('user_id', 2)->count());
    }

    #[Test]
    public function guest_cannot_flag_user(): void
    {
        $this->assertSame(401, $this->create($this->payload(), null)->getStatusCode());
    }

    #[Test]
    public function member_cannot_flag_self_by_default(): void
    {
        $this->assertSame(403, $this->create($this->payload(2))->getStatusCode());
    }

    #[Test]
    public function actor_without_account_flag_permission_cannot_create(): void
    {
        $this->database()->table('group_permission')->where('permission', 'user.flag')->delete();
        $this->assertSame(403, $this->create($this->payload())->getStatusCode());
    }

    #[Test]
    public function missing_or_dual_target_is_rejected_without_saving(): void
    {
        $payload = $this->payload();
        unset($payload['data']['relationships']);
        $this->assertSame(422, $this->create($payload)->getStatusCode());
        $payload = $this->payload();
        $payload['data']['relationships']['post'] = ['data' => ['type' => 'posts', 'id' => '1']];
        $this->assertSame(422, $this->create($payload)->getStatusCode());
        $this->assertSame(4, Flag::count());
    }

    #[Test]
    public function nonexistent_user_is_rejected(): void
    {
        $this->assertSame(404, $this->create($this->payload(999999))->getStatusCode());
    }

    #[Test]
    public function explicit_null_post_with_account_target_is_valid_but_wrong_type_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['data']['relationships']['post'] = ['data' => null];
        $response = $this->create($payload);
        $this->assertSame(201, $response->getStatusCode(), $response->getBody()->getContents());
        $payload = $this->payload();
        $payload['data']['relationships']['targetUser']['data']['type'] = 'posts';
        $this->assertSame(400, $this->create($payload)->getStatusCode());
        $this->assertSame(1, Flag::where('target_user_id', 6)->count());
    }

    #[Test]
    public function manual_api_cannot_forge_reporter_or_overwrite_programmatic_report(): void
    {
        $service = $this->app()->getContainer()->make(UserFlagger::class);
        $service->flag(User::findOrFail(6), 'spam-detector', 'spam', 'Programmatic report', User::findOrFail(2));
        $response = $this->create($this->payload());
        $this->assertSame(201, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertSame(2, Flag::where('target_user_id', 6)->count());
        $this->assertSame('Programmatic report', Flag::where('target_user_id', 6)->where('type', 'spam-detector')->first()->reason_detail);
        $payload = $this->payload();
        $payload['data']['relationships']['user'] = ['data' => ['type' => 'users', 'id' => '1']];
        $payload['data']['attributes']['type'] = 'spam-detector';
        $forged = $this->create($payload);
        $this->assertNotSame(500, $forged->getStatusCode());
        $this->assertSame(0, Flag::where('target_user_id', 6)->where('user_id', 1)->count());
        $this->assertSame(2, Flag::where('target_user_id', 6)->count());
    }

    #[Test]
    public function hidden_user_cannot_be_flagged_by_api_or_actor_service_but_trusted_server_can_report(): void
    {
        $this->extend((new ModelVisibility(User::class))->scope(function (User $actor, Builder $query) {
            if ($actor->id === 2) {
                $query->where('users.id', '<>', 6);
            }
        }));
        $this->assertSame(404, $this->create($this->payload())->getStatusCode());
        $this->assertSame(0, Flag::where('target_user_id', 6)->count());
        $service = $this->app()->getContainer()->make(UserFlagger::class);

        try {
            $service->flag(User::findOrFail(6), 'user', 'spam', null, User::findOrFail(2));
            $this->fail('An actor cannot flag a hidden target');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }
        $flag = $service->flag(User::findOrFail(6), 'spam-check', 'spam');
        $this->assertSame(6, $flag->target_user_id);
        $this->assertNull($flag->user_id);
    }

    #[Test]
    public function account_created_event_occurs_only_for_a_new_unresolved_report(): void
    {
        $events = 0;
        $this->app()->getContainer()->make(Dispatcher::class)->listen(UserFlagCreated::class, function () use (&$events) {
            $events++;
        });
        $this->assertSame(201, $this->create($this->payload())->getStatusCode());
        $this->assertSame(201, $this->create($this->payload())->getStatusCode());
        $this->assertSame(1, $events);
    }

    #[Test]
    public function reason_or_detail_is_required_and_length_is_bounded(): void
    {
        $payload = $this->payload();
        $payload['data']['attributes'] = [];
        $this->assertSame(422, $this->create($payload)->getStatusCode());
        $payload['data']['attributes'] = ['reasonDetail' => str_repeat('x', 2001)];
        $this->assertSame(422, $this->create($payload)->getStatusCode());
        $payload['data']['attributes'] = ['reasonDetail' => 'Spam in the biography'];
        $response = $this->create($payload);
        $this->assertSame(201, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function moderators_see_distinct_post_and_account_targets(): void
    {
        $response = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 4]));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $flags = json_decode($body, true)['data'];
        $this->assertCount(3, $flags);
        $targets = array_map(fn ($f) => $f['relationships']['targetUser']['data']['id'] ?? null, $flags);
        $this->assertEqualsCanonicalizing([null, '2', '3'], $targets);
    }

    #[Test]
    public function paginated_list_counts_distinct_targets_without_colliding_post_and_account_ids(): void
    {
        $this->app();
        $this->database()->table('flags')->insert([
            'post_id' => null,
            'target_user_id' => 1,
            'type' => 'user',
            'user_id' => 2,
            'reason' => 'spam',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->send(
            $this->request('GET', '/api/flags', ['authenticatedAs' => 4])
                ->withQueryParams(['page' => ['limit' => '1', 'offset' => '1']])
        );
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(4, $data['meta']['page']['total']);

        $hidden = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 2]));
        $hiddenBody = $hidden->getBody()->getContents();
        $this->assertSame(200, $hidden->getStatusCode(), $hiddenBody);
        $this->assertSame(0, json_decode($hiddenBody, true)['meta']['page']['total']);
    }

    #[Test]
    public function normal_reporter_cannot_list_or_include_private_account_reports(): void
    {
        $list = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 2]));
        $this->assertSame([], json_decode($list->getBody()->getContents(), true)['data']);
        $user = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 2])->withQueryParams(['include' => 'flags,flags.user']));
        $body = $user->getBody()->getContents();
        $this->assertSame(200, $user->getStatusCode(), $body);
        $data = json_decode($body, true);
        $this->assertArrayNotHasKey('flags', $data['data']['relationships'] ?? []);
        $this->assertSame([], array_values(array_filter($data['included'] ?? [], fn ($item) => $item['type'] === 'flags')));
    }

    #[Test]
    public function post_only_moderator_cannot_see_or_dismiss_account_reports(): void
    {
        $response = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 5]));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $this->assertCount(1, json_decode($body, true)['data']);
        $delete = $this->send($this->request('DELETE', '/api/users/3/flags', ['authenticatedAs' => 5]));
        $this->assertSame(403, $delete->getStatusCode());
        $this->assertSame(2, Flag::where('target_user_id', 3)->count());
    }

    #[Test]
    public function account_report_includes_preserve_the_reporters_instead_of_hydrating_the_target_as_reporter(): void
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 4])->withQueryParams(['include' => 'flags,flags.user,flags.targetUser']));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $included = collect(json_decode($body, true)['included']);
        $flags = $included->where('type', 'flags')->keyBy('id');
        $this->assertCount(2, $flags);
        $this->assertSame('2', $flags['2']['relationships']['user']['data']['id']);
        $this->assertSame('4', $flags['3']['relationships']['user']['data']['id']);
        $this->assertSame('3', $flags['2']['relationships']['targetUser']['data']['id']);
        $this->assertSame('3', $flags['3']['relationships']['targetUser']['data']['id']);
        $users = $included->where('type', 'users')->keyBy('id');
        $this->assertSame('normal', $users['2']['attributes']['username']);
        $this->assertSame('testuser4', $users['4']['attributes']['username']);

        $postResponse = $this->send($this->request('GET', '/api/posts/1', ['authenticatedAs' => 4])->withQueryParams(['include' => 'flags,flags.user']));
        $postBody = $postResponse->getBody()->getContents();
        $this->assertSame(200, $postResponse->getStatusCode(), $postBody);
        $postFlag = collect(json_decode($postBody, true)['included'])->where('type', 'flags')->firstWhere('id', '1');
        $this->assertSame('2', $postFlag['relationships']['user']['data']['id']);
        $this->assertSame('1', $postFlag['relationships']['post']['data']['id']);
    }

    #[Test]
    public function moderator_can_include_reporters_and_dismiss_only_one_account(): void
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 4])->withQueryParams(['include' => 'flags,flags.user']));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);
        $this->assertCount(2, $data['data']['relationships']['flags']['data']);
        $delete = $this->send($this->request('DELETE', '/api/users/3/flags', ['authenticatedAs' => 4]));
        $this->assertSame(204, $delete->getStatusCode(), $delete->getBody()->getContents());
        $this->assertSame(0, Flag::where('target_user_id', 3)->count());
        $this->assertSame(2, Flag::count());
        $this->assertNotNull(Flag::find(1));
        $this->assertNotNull(Flag::find(4));
    }

    #[Test]
    public function forum_and_new_flag_counts_include_separate_account_targets(): void
    {
        $response = $this->send($this->request('GET', '/api', ['authenticatedAs' => 4]));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);
        $this->assertSame(3, $data['data']['attributes']['flagCount']);
        $this->assertTrue($data['data']['attributes']['canViewFlags']);
        $user = $this->send($this->request('GET', '/api/users/4', ['authenticatedAs' => 4]));
        $this->assertSame(3, json_decode($user->getBody()->getContents(), true)['data']['attributes']['newFlagCount']);
    }

    #[Test]
    public function programmatic_extender_creates_deduplicated_actorless_user_report(): void
    {
        $this->extend((new UserFlags())->on(FlagTestEvent::class, fn ($event) => $event->target, 'spam', fn ($event) => $event->detail));
        $container = $this->app()->getContainer();
        $event = new FlagTestEvent(User::findOrFail(6), 'Automated biography check');
        $container->make(Dispatcher::class)->dispatch($event);
        $container->make(Dispatcher::class)->dispatch($event);
        $flags = Flag::where('target_user_id', 6)->get();
        $this->assertCount(1, $flags);
        $this->assertNull($flags[0]->post_id);
        $this->assertNull($flags[0]->user_id);
        $this->assertSame('system', $flags[0]->type);
        $this->assertSame('Automated biography check', $flags[0]->reason_detail);
        $response = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 4]));
        $body = $response->getBody()->getContents();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $this->assertCount(4, json_decode($body, true)['data']);
    }

    #[Test]
    public function explicit_actor_programmatic_calls_preserve_permissions(): void
    {
        $container = $this->app()->getContainer();
        $service = $container->make(UserFlagger::class);
        $this->expectException(\Flarum\User\Exception\PermissionDeniedException::class);
        $service->flag(User::findOrFail(2), 'user', 'spam', null, User::findOrFail(2));
    }

    #[Test]
    public function migration_has_nullable_post_and_cascading_account_target(): void
    {
        $columns = $this->database()->getSchemaBuilder()->getColumns('flags');
        $post = collect($columns)->firstWhere('name', 'post_id');
        $target = collect($columns)->firstWhere('name', 'target_user_id');
        $this->assertTrue($post['nullable']);
        $this->assertTrue($target['nullable']);
        $keys = $this->database()->getSchemaBuilder()->getForeignKeys('flags');
        $key = collect($keys)->first(fn ($key) => $key['columns'] === ['target_user_id']);
        $this->assertSame($this->database()->getTablePrefix().'users', $key['foreign_table']);
        $this->assertSame('cascade', strtolower($key['on_delete']));
    }
}

class FlagTestEvent
{
    public function __construct(public User $target, public string $detail)
    {
    }
}
