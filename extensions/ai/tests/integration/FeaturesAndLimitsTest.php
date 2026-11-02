<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Carbon\Carbon;
use Flarum\Ai\Ai;
use Flarum\Ai\Exception\FeatureDisabledException;
use Flarum\Ai\Exception\LimitReachedException;
use Flarum\Ai\Exception\LimitType;
use Flarum\Ai\Exception\NotConfiguredException;
use Flarum\Ai\Extend\Ai as AiExtender;
use Flarum\Ai\Testing\InteractsWithAi;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laminas\Diactoros\Response\EmptyResponse;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Laravel\Ai\agent;

/**
 * Features extensions register, and the usage ledger and limits every AI call
 * goes through. Provider requests go through the SDK's real gateway code,
 * with HTTP faked; each call uses 12 input and 5 output tokens.
 */
class FeaturesAndLimitsTest extends ConsoleTestCase
{
    use InteractsWithAi;
    use RetrievesAuthorizedUsers;

    protected const FEATURE = 'test-ext.writer';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
        $this->extend((new AiExtender())->feature(self::FEATURE, 'test-ext.writer_label', 'test-ext.writer_help'));

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);

        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']);
        $this->grantAiFeature(self::FEATURE, Group::MEMBER_ID);
    }

    protected function fakeProviders(): void
    {
        Http::fake(['*.test/*' => Http::response([
            'id' => 'msg_1',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-test',
            'content' => [['type' => 'text', 'text' => 'Hi']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 12, 'output_tokens' => 5],
        ])]);
    }

    protected function ai(): Ai
    {
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeProviders();

        return $ai;
    }

    protected function user(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    protected function usage(): array
    {
        return $this->database()->table('ai_usage')->orderBy('id')->get()
            ->map(fn ($row) => [
                'feature' => $row->feature,
                'actor' => $row->actor,
                'user_id' => $row->user_id === null ? null : (int) $row->user_id,
                'connection' => $row->connection,
                'driver' => $row->driver,
                'model' => $row->model,
                'kind' => $row->kind,
                'tokens' => (int) $row->input_tokens + (int) $row->output_tokens,
            ])
            ->all();
    }

    protected function requests(): array
    {
        return Http::recorded()->map(fn ($pair) => ['url' => $pair[0]->url(), 'model' => $pair[0]->data()['model'] ?? null])->all();
    }

    protected function expectLimit(LimitType $type, callable $call): LimitReachedException
    {
        try {
            $call();
        } catch (LimitReachedException $e) {
            $this->assertSame($type, $e->limit);

            return $e;
        }

        $this->fail("The call should have been stopped by the {$type->value} limit.");
    }

    #[Test]
    public function a_features_calls_are_recorded_for_it_and_its_member(): void
    {
        $this->enableAiFeature(self::FEATURE);

        $this->ai()->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');

        $this->assertSame([[
            'feature' => self::FEATURE,
            'actor' => 'member',
            'user_id' => 2,
            'connection' => 'claude',
            'driver' => 'anthropic',
            'model' => 'claude-test',
            'kind' => 'text',
            'tokens' => 17,
        ]], $this->usage());
    }

    #[Test]
    public function calls_made_outside_any_feature_are_recorded_too(): void
    {
        $this->ai();

        agent('Be brief')->prompt('Hello');

        $this->assertSame(['feature' => null, 'actor' => null, 'user_id' => null], array_intersect_key($this->usage()[0], array_flip(['feature', 'actor', 'user_id'])));
        $this->assertSame(17, $this->usage()[0]['tokens']);
    }

    #[Test]
    public function a_call_must_say_who_it_is_for(): void
    {
        $this->enableAiFeature(self::FEATURE);

        $this->expectException(LogicException::class);

        $this->ai()->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');
    }

    #[Test]
    public function a_feature_is_off_until_the_admin_turns_it_on(): void
    {
        $ai = $this->ai();

        $this->assertFalse($ai->feature(self::FEATURE)->asSystem()->available());

        try {
            $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should not have been made.');
        } catch (FeatureDisabledException $e) {
            $this->assertSame(self::FEATURE, $e->feature);
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_feature_with_no_connection_to_use_is_not_configured(): void
    {
        $this->setting('flarum-ai.connections', '{}');
        $this->setting('flarum-ai.default_connection', '');
        $this->enableAiFeature(self::FEATURE);

        $this->expectException(NotConfiguredException::class);

        $this->ai()->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
    }

    #[Test]
    public function members_and_guests_need_the_features_permission(): void
    {
        $this->enableAiFeature(self::FEATURE);
        $this->database()->table('group_permission')->where('permission', 'ai.feature.'.self::FEATURE)->update(['group_id' => Group::MODERATOR_ID]);

        $ai = $this->ai();

        $this->assertFalse($ai->feature(self::FEATURE)->as($this->user(2))->available());
        $this->assertFalse($ai->feature(self::FEATURE)->as(new Guest())->available());
        $this->assertTrue($ai->feature(self::FEATURE)->asSystem()->available(), 'The forum\'s own calls need no permission.');

        $this->expectException(PermissionDeniedException::class);

        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');
    }

    #[Test]
    public function a_feature_uses_the_connection_and_model_the_admin_chose(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'model' => 'claude-house']);
        $this->aiConnection('other', ['driver' => 'anthropic', 'url' => 'https://other.test/v1']);
        $this->setting('flarum-ai.default_connection', 'claude');
        $this->enableAiFeature(self::FEATURE, ['connection' => 'other', 'model' => 'claude-special']);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
        agent('Be brief')->prompt('Hello');

        $this->assertSame([
            ['url' => 'https://other.test/v1/messages', 'model' => 'claude-special'],
            // Outside the feature: the default connection, with its default model.
            ['url' => 'https://api.anthropic.test/v1/messages', 'model' => 'claude-house'],
        ], $this->requests());
        $this->assertSame(['other', 'claude'], array_column($this->usage(), 'connection'));
    }

    #[Test]
    public function a_connections_monthly_limit_stops_its_calls_until_the_month_ends(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'monthly_tokens' => 20]);

        $this->ai();

        agent('Be brief')->prompt('Hello');
        agent('Be brief')->prompt('Hello');

        $e = $this->expectLimit(LimitType::Connection, fn () => agent('Be brief')->prompt('Hello'));
        $this->assertSame('claude', $e->name);
        $this->assertSame(Carbon::now('UTC')->addMonthNoOverflow()->startOfMonth()->toIso8601String(), $e->resetsAt->toIso8601String());
        $this->assertCount(2, Http::recorded());

        Carbon::setTestNow(Carbon::now()->addMonthNoOverflow()->startOfMonth());

        try {
            agent('Be brief')->prompt('Hello');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertCount(3, Http::recorded());
    }

    #[Test]
    public function streams_are_limited_too(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'monthly_tokens' => 10]);

        $this->ai();
        agent('Be brief')->prompt('Hello');

        $this->expectLimit(LimitType::Connection, fn () => iterator_to_array(agent('Be brief')->stream('Hello')));
        $this->assertCount(1, Http::recorded());
    }

    #[Test]
    public function a_stream_read_after_the_call_returned_still_counts_for_its_feature(): void
    {
        $this->enableAiFeature(self::FEATURE, ['daily_tokens' => 10]);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');

        // Nothing is sent until the stream is read, by when the feature call has returned.
        $stream = $ai->feature(self::FEATURE)->asSystem()->stream(agent('Be brief'), 'Hello');

        $this->expectLimit(LimitType::Feature, fn () => iterator_to_array($stream));
        $this->assertCount(1, Http::recorded());
    }

    #[Test]
    public function a_queued_call_is_made_and_counted_as_its_feature_and_member(): void
    {
        $this->enableAiFeature(self::FEATURE);

        // The test suite's queue runs jobs straight away, through the same serialization as a worker.
        $this->ai()->feature(self::FEATURE)->as($this->user(2))->queue(agent('Be brief'), 'Hello');

        $this->assertCount(1, Http::recorded());
        $this->assertSame([self::FEATURE, 'member', 2], [$this->usage()[0]['feature'], $this->usage()[0]['actor'], $this->usage()[0]['user_id']]);
    }

    #[Test]
    public function a_features_daily_limit_stops_its_calls(): void
    {
        $this->enableAiFeature(self::FEATURE, ['daily_tokens' => 10]);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');

        $this->expectLimit(LimitType::Feature, fn () => $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello'));

        // Calls outside the feature aren't held by its limit.
        agent('Be brief')->prompt('Hello');

        $this->assertCount(2, Http::recorded());
    }

    #[Test]
    public function a_members_daily_limit_stops_their_calls_but_not_admins_or_the_forums_own(): void
    {
        $this->enableAiFeature(self::FEATURE);
        $this->setting('flarum-ai.member_daily_tokens', '10');

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');

        $e = $this->expectLimit(LimitType::Member, fn () => $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello'));
        $this->assertSame('normal', $e->name);

        $ai->feature(self::FEATURE)->as($this->user(1))->prompt(agent('Be brief'), 'Hello');
        $ai->feature(self::FEATURE)->as($this->user(1))->prompt(agent('Be brief'), 'Hello');
        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');

        $this->assertCount(4, Http::recorded());
    }

    #[Test]
    public function guests_share_one_daily_limit(): void
    {
        $this->enableAiFeature(self::FEATURE);
        $this->grantAiFeature(self::FEATURE, Group::GUEST_ID);
        $this->setting('flarum-ai.guest_daily_tokens', '10');

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->as(new Guest())->prompt(agent('Be brief'), 'Hello');

        $this->assertSame(['guest', null], [$this->usage()[0]['actor'], $this->usage()[0]['user_id']]);

        $this->expectLimit(LimitType::Guests, fn () => $ai->feature(self::FEATURE)->as(new Guest())->prompt(agent('Be brief'), 'Hello'));

        // Members aren't held by the guests' limit.
        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');
        $this->assertCount(2, Http::recorded());
    }

    #[Test]
    public function the_forum_tells_each_member_or_guest_which_features_they_can_use(): void
    {
        $this->enableAiFeature(self::FEATURE);

        $forum = fn (?int $actor) => json_decode((string) $this->send($this->request('GET', '/api', $actor ? ['authenticatedAs' => $actor] : []))->getBody(), true)['data']['attributes']['aiFeatures'];

        $this->assertSame([self::FEATURE], $forum(2));
        $this->assertSame([], $forum(null), 'Guests have no permission for it.');
    }

    #[Test]
    public function errors_reach_api_clients_with_their_status_and_code(): void
    {
        $this->extend((new Extend\Routes('api'))->get('/ai-test-call', 'ai-test-call', AiTestCallHandler::class));
        $this->extend((new AiExtender())->feature('test-ext.off', 'test-ext.off_label'));
        $this->setting('flarum-ai.member_daily_tokens', '10');

        $this->enableAiFeature(self::FEATURE);
        $this->app();
        $this->fakeProviders();

        $call = fn () => $this->send($this->request('GET', '/api/ai-test-call', ['authenticatedAs' => 2]));

        $this->assertSame(204, $call()->getStatusCode());

        $limited = $call();
        $error = json_decode((string) $limited->getBody(), true)['errors'][0];
        $this->assertSame(429, $limited->getStatusCode());
        $this->assertSame('ai_limit_reached', $error['code']);
        $this->assertSame('member', $error['meta']['limit']);
        $this->assertSame(Carbon::now('UTC')->addDay()->startOfDay()->toIso8601String(), $error['meta']['resetsAt']);
        $this->assertGreaterThan(0, $error['meta']['retryAfter']);

        $disabled = $this->send($this->request('GET', '/api/ai-test-call', ['authenticatedAs' => 1])->withQueryParams(['feature' => 'test-ext.off']));
        $this->assertSame(403, $disabled->getStatusCode());
        $this->assertSame('ai_feature_disabled', json_decode((string) $disabled->getBody(), true)['errors'][0]['code']);
    }

    #[Test]
    public function the_admin_sees_usage_totals_and_no_one_else_does(): void
    {
        $this->enableAiFeature(self::FEATURE);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');
        agent('Be brief')->prompt('Hello');

        $response = $this->send($this->request('GET', '/api/ai/usage', ['authenticatedAs' => 1]));
        $usage = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([['name' => 'claude', 'calls' => 2, 'tokens' => 34]], $usage['connections']);
        $this->assertEqualsCanonicalizing(
            [['name' => self::FEATURE, 'calls' => 1, 'tokens' => 17], ['name' => null, 'calls' => 1, 'tokens' => 17]],
            $usage['features']
        );
        $this->assertSame([['id' => 2, 'username' => 'normal', 'calls' => 1, 'tokens' => 17]], $usage['members']);
        $this->assertCount(30, $usage['days']);
        $this->assertSame(['date' => Carbon::now()->toDateString(), 'tokens' => 34], end($usage['days']));

        $this->assertSame(403, $this->send($this->request('GET', '/api/ai/usage', ['authenticatedAs' => 2]))->getStatusCode());
        $this->assertContains($this->send($this->request('GET', '/api/ai/usage'))->getStatusCode(), [401, 403]);
    }

    #[Test]
    public function the_admin_page_lists_registered_features(): void
    {
        $response = $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches));
        $payload = json_decode(html_entity_decode($matches[1]), true);

        $this->assertSame([[
            'key' => self::FEATURE,
            'label' => 'test-ext.writer_label',
            'description' => 'test-ext.writer_help',
            'extension' => null,
            'permission' => 'ai.feature.'.self::FEATURE,
        ]], $payload['flarum-ai']['features']);
    }

    #[Test]
    public function feature_keys_are_checked_when_registered(): void
    {
        foreach (['NoDot', 'acme.Upper', 'acme.'.str_repeat('x', 85), '.leading'] as $key) {
            try {
                (new AiExtender())->feature($key, 'label');
                $this->fail("\"$key\" should have been refused.");
            } catch (InvalidArgumentException $e) {
                $this->addToAssertionCount(1);
            }
        }

        (new AiExtender())->feature('acme.'.str_repeat('x', 84), 'label');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function a_feature_key_registered_twice_is_refused(): void
    {
        $this->extend((new AiExtender())->feature(self::FEATURE, 'another_label'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('registered twice');

        $this->app()->getContainer()->make(Ai::class)->feature(self::FEATURE);
    }

    protected function save(array $settings): int
    {
        return $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => $settings]))->getStatusCode();
    }

    protected function storedFeatures(): array
    {
        return json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.features')->value('value'), true) ?? [];
    }

    #[Test]
    public function feature_settings_are_validated_and_settings_left_out_are_kept(): void
    {
        $this->setting('flarum-ai.features', json_encode([
            'gone-ext.thing' => ['enabled' => true],
            self::FEATURE => ['enabled' => true, 'daily_tokens' => 7],
        ]));

        // A save that leaves out a registered feature doesn't reset it.
        $this->assertSame(204, $this->save(['flarum-ai.features' => '{}']));
        $this->assertSame(['gone-ext.thing' => ['enabled' => true], self::FEATURE => ['enabled' => true, 'daily_tokens' => 7]], $this->storedFeatures());

        $this->assertSame(204, $this->save(['flarum-ai.features' => json_encode([
            self::FEATURE => ['enabled' => true, 'connection' => 'claude', 'model' => ' claude-x ', 'daily_tokens' => '500'],
            'unknown-ext.thing' => ['enabled' => true],
        ])]));

        $this->assertSame([
            'gone-ext.thing' => ['enabled' => true],
            self::FEATURE => ['enabled' => true, 'connection' => 'claude', 'model' => 'claude-x', 'daily_tokens' => 500],
        ], $this->storedFeatures());

        $this->assertSame(422, $this->save(['flarum-ai.features' => json_encode([self::FEATURE => ['enabled' => true, 'connection' => 'missing']])]));
        $this->assertSame(422, $this->save(['flarum-ai.features' => json_encode([self::FEATURE => ['enabled' => true, 'daily_tokens' => -5]])]));
        $this->assertSame(422, $this->save(['flarum-ai.features' => json_encode([self::FEATURE => ['enabled' => 'yes']])]));
        $this->assertSame(422, $this->save(['flarum-ai.features' => '["x"]']));
    }

    #[Test]
    public function limits_are_validated(): void
    {
        foreach (['flarum-ai.member_daily_tokens', 'flarum-ai.guest_daily_tokens'] as $setting) {
            $this->assertSame(204, $this->save([$setting => '1000']));
            $this->assertSame(204, $this->save([$setting => '']));
            $this->assertSame(422, $this->save([$setting => 'lots']));
            $this->assertSame(422, $this->save([$setting => '0']));
            $this->assertSame(422, $this->save([$setting => '1e3']));
        }

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'monthly_tokens' => '100000', 'model' => 'claude-x']])]));
        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'monthly_tokens' => 1.5]])]));

        $stored = json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.connections')->value('value'), true);
        $this->assertSame(['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'model' => 'claude-x', 'monthly_tokens' => 100000], $stored['claude']);
    }

    #[Test]
    public function old_usage_is_pruned(): void
    {
        $this->app();

        $row = ['connection' => 'claude', 'driver' => 'anthropic', 'kind' => 'text', 'input_tokens' => 1, 'output_tokens' => 1];

        $this->database()->table('ai_usage')->insert([
            $row + ['created_at' => Carbon::now()->subDays(91)],
            $row + ['created_at' => Carbon::now()->subDays(89)],
        ]);

        $this->assertStringContainsString('Deleted 1 AI usage records.', $this->runCommand(['command' => 'ai:prune-usage']));
        $this->assertSame(1, $this->database()->table('ai_usage')->count());
    }
}

/**
 * A route that makes one feature call as the member making the request, so the
 * errors can be seen as an API client sees them.
 */
class AiTestCallHandler implements RequestHandlerInterface
{
    public function __construct(
        protected Ai $ai
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $feature = $request->getQueryParams()['feature'] ?? 'test-ext.writer';

        $this->ai->feature($feature)->as(RequestUtil::getActor($request))->prompt(agent('Be brief'), 'Hello');

        return new EmptyResponse(204);
    }
}
