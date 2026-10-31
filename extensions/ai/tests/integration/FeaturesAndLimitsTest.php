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
use Flarum\Ai\Extend\Ai as AiExtender;
use Flarum\Ai\Features\FeatureUnavailableException;
use Flarum\Ai\Usage\LimitReachedException;
use Flarum\Group\Group;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;

use function Laravel\Ai\agent;

/**
 * Features extensions register, and the usage ledger and limits every AI call
 * goes through. Provider requests go through the SDK's real gateway code,
 * with HTTP faked; each call uses 12 input and 5 output tokens.
 */
class FeaturesAndLimitsTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    protected const FEATURE = 'test-ext.writer';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
        $this->extend((new AiExtender())->feature(self::FEATURE, 'test-ext.writer_label', 'test-ext.writer_help'));

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'ai.feature.'.self::FEATURE],
            ],
        ]);

        $this->connections(['claude' => $this->anthropic('https://api.anthropic.test/v1')], 'claude');
    }

    protected function anthropic(string $url, array $extra = []): array
    {
        return ['driver' => 'anthropic', 'key' => 'k', 'url' => $url] + $extra;
    }

    protected function connections(array $connections, ?string $default = null): void
    {
        $this->setting('flarum-ai.connections', json_encode($connections));
        $this->setting('flarum-ai.default_connection', $default ?? '');
    }

    protected function feature(array $settings): void
    {
        $this->setting('flarum-ai.features', json_encode([self::FEATURE => $settings]));
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

    #[Test]
    public function a_features_calls_are_recorded_for_it_and_its_member(): void
    {
        $this->feature(['enabled' => true]);

        $this->ai()->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');

        $this->assertSame([[
            'feature' => self::FEATURE,
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

        $this->assertSame(null, $this->usage()[0]['feature']);
        $this->assertSame(null, $this->usage()[0]['user_id']);
        $this->assertSame(17, $this->usage()[0]['tokens']);
    }

    #[Test]
    public function a_feature_is_off_until_the_admin_turns_it_on(): void
    {
        $ai = $this->ai();

        $this->assertFalse($ai->feature(self::FEATURE)->available());

        try {
            $ai->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should not have been made.');
        } catch (FeatureUnavailableException $e) {
            $this->assertSame('disabled', $e->reason);
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_feature_with_no_connection_to_use_is_unavailable(): void
    {
        $this->connections([]);
        $this->feature(['enabled' => true]);

        $this->expectException(FeatureUnavailableException::class);

        $this->ai()->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');
    }

    #[Test]
    public function a_member_needs_the_features_permission(): void
    {
        $this->feature(['enabled' => true]);
        $this->database()->table('group_permission')->where('permission', 'ai.feature.'.self::FEATURE)->update(['group_id' => Group::MODERATOR_ID]);

        $ai = $this->ai();

        $this->assertFalse($ai->feature(self::FEATURE)->as($this->user(2))->available());
        $this->assertTrue($ai->feature(self::FEATURE)->available(), 'A call made for no member needs no permission.');

        $this->expectException(PermissionDeniedException::class);

        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');
    }

    #[Test]
    public function a_feature_uses_the_connection_and_model_the_admin_chose(): void
    {
        $this->connections([
            'claude' => $this->anthropic('https://api.anthropic.test/v1', ['model' => 'claude-house']),
            'other' => $this->anthropic('https://other.test/v1'),
        ], 'claude');
        $this->feature(['enabled' => true, 'connection' => 'other', 'model' => 'claude-special']);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');
        agent('Be brief')->prompt('Hello');

        $this->assertSame([
            ['url' => 'https://other.test/v1/messages', 'model' => 'claude-special'],
            // Outside the feature: the default connection, with its default model.
            ['url' => 'https://api.anthropic.test/v1/messages', 'model' => 'claude-house'],
        ], $this->requests());
        $this->assertSame(['other', 'claude'], array_column($this->usage(), 'connection'));
    }

    #[Test]
    public function a_connections_monthly_limit_stops_its_calls(): void
    {
        $this->connections(['claude' => $this->anthropic('https://api.anthropic.test/v1', ['monthly_tokens' => 20])], 'claude');

        $this->ai();

        agent('Be brief')->prompt('Hello');
        agent('Be brief')->prompt('Hello');

        try {
            agent('Be brief')->prompt('Hello');
            $this->fail('The third call should have been stopped.');
        } catch (LimitReachedException $e) {
            $this->assertSame('connection', $e->limit);
        }

        $this->assertCount(2, Http::recorded());

        // A new month starts afresh.
        Carbon::setTestNow(Carbon::now()->addMonthNoOverflow()->startOfMonth());

        try {
            agent('Be brief')->prompt('Hello');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertCount(3, Http::recorded());
    }

    #[Test]
    public function a_features_daily_limit_stops_its_calls(): void
    {
        $this->feature(['enabled' => true, 'daily_tokens' => 10]);

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');

        try {
            $ai->feature(self::FEATURE)->prompt(agent('Be brief'), 'Hello');
            $this->fail('The second call should have been stopped.');
        } catch (LimitReachedException $e) {
            $this->assertSame('feature', $e->limit);
        }

        // Calls outside the feature aren't held by its limit.
        agent('Be brief')->prompt('Hello');

        $this->assertCount(2, Http::recorded());
    }

    #[Test]
    public function a_members_daily_limit_stops_their_calls_but_not_admins(): void
    {
        $this->feature(['enabled' => true]);
        $this->setting('flarum-ai.member_daily_tokens', '10');

        $ai = $this->ai();
        $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');

        try {
            $ai->feature(self::FEATURE)->as($this->user(2))->prompt(agent('Be brief'), 'Hello');
            $this->fail('The member\'s second call should have been stopped.');
        } catch (LimitReachedException $e) {
            $this->assertSame('member', $e->limit);
        }

        $ai->feature(self::FEATURE)->as($this->user(1))->prompt(agent('Be brief'), 'Hello');
        $ai->feature(self::FEATURE)->as($this->user(1))->prompt(agent('Be brief'), 'Hello');

        $this->assertCount(3, Http::recorded());
    }

    #[Test]
    public function the_admin_sees_usage_totals(): void
    {
        $this->feature(['enabled' => true]);

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
    }

    #[Test]
    public function the_admin_page_lists_registered_features(): void
    {
        $response = $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]));

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);
        $payload = json_decode(html_entity_decode($matches[1]), true);

        $this->assertSame([[
            'key' => self::FEATURE,
            'label' => 'test-ext.writer_label',
            'description' => 'test-ext.writer_help',
            'extension' => null,
            'permission' => 'ai.feature.'.self::FEATURE,
        ]], $payload['flarum-ai']['features']);
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
    public function feature_settings_are_validated_and_settings_of_absent_features_kept(): void
    {
        $this->setting('flarum-ai.features', json_encode(['gone-ext.thing' => ['enabled' => true]]));

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
        $this->assertSame(204, $this->save(['flarum-ai.member_daily_tokens' => '1000']));
        $this->assertSame(204, $this->save(['flarum-ai.member_daily_tokens' => '']));
        $this->assertSame(422, $this->save(['flarum-ai.member_daily_tokens' => 'lots']));
        $this->assertSame(422, $this->save(['flarum-ai.member_daily_tokens' => '0']));

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'monthly_tokens' => '100000', 'model' => 'claude-x']])]));
        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'monthly_tokens' => 1.5]])]));

        $stored = json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.connections')->value('value'), true);
        $this->assertSame(['driver' => 'anthropic', 'model' => 'claude-x', 'monthly_tokens' => 100000, 'key' => 'k'], $stored['claude']);
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
