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
use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Testing\InteractsWithAi;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use PHPUnit\Framework\Attributes\Test;

use function Laravel\Ai\agent;

/**
 * What happens when a model a feature uses is deprecated or goes away: the
 * daily check, the admin's warnings, and calls that find the model gone.
 */
class ModelHealthTest extends ConsoleTestCase
{
    use InteractsWithAi;
    use RetrievesAuthorizedUsers;

    protected const WRITER = 'test-ext.writer';

    protected const SUMMARIES = 'test-ext.summaries';

    /**
     * What the provider lists, by model ID: null for a model it still offers,
     * a date for one it retires then.
     *
     * @var array<string, string|null>
     */
    protected array $offered = [];

    /**
     * Models the provider answers a call to with a 404.
     *
     * @var string[]
     */
    protected array $gone = [];

    /**
     * Answers every request in place of the faked provider, when set. Faking
     * HTTP again doesn't replace the first fake: the first that matches answers.
     */
    protected ?\Closure $answer = null;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-06 12:00:00');

        $this->extension('flarum-ai');
        $this->extend((new AiExtender())
            ->feature(self::WRITER, 'test-ext.writer_label')
            ->feature(self::SUMMARIES, 'test-ext.summaries_label'));

        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'model' => 'claude-default']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function fakeProvider(): void
    {
        Http::fake(function (Request $request) {
            if ($this->answer) {
                return ($this->answer)($request);
            }

            if (str_contains($request->url(), '/models')) {
                return Http::response(['data' => array_map(
                    fn ($id, $retires) => ['id' => $id, 'display_name' => $id, 'deprecated_at' => $retires ? '2026-10-01T00:00:00Z' : null, 'retires_at' => $retires],
                    array_keys($this->offered),
                    $this->offered
                ), 'has_more' => false]);
            }

            $model = $request->data()['model'] ?? null;

            if (in_array($model, $this->gone, true)) {
                return Http::response(['type' => 'error', 'error' => ['type' => 'not_found_error', 'message' => "model: $model"]], 404);
            }

            return Http::response([
                'id' => 'msg_1',
                'type' => 'message',
                'role' => 'assistant',
                'model' => $model,
                'content' => [['type' => 'text', 'text' => "Hi from $model"]],
                'stop_reason' => 'end_turn',
                'usage' => ['input_tokens' => 12, 'output_tokens' => 5],
            ]);
        });
    }

    protected function health(): ModelHealth
    {
        $health = $this->app()->getContainer()->make(ModelHealth::class);
        $this->fakeProvider();

        return $health;
    }

    protected function ai(): Ai
    {
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeProvider();

        return $ai;
    }

    /**
     * @return array<string, array{status: string, since: string, retiresAt: string|null, features: string[]}>
     */
    protected function issues(): array
    {
        $issues = [];

        foreach ($this->app()->getContainer()->make(ModelHealth::class)->forAdmin() as $issue) {
            $issues[$issue['connection'].'/'.$issue['model']] = array_diff_key($issue, ['connection' => 0, 'model' => 0]);
        }

        return $issues;
    }

    protected function calledModels(): array
    {
        return Http::recorded()
            ->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/messages'))
            ->map(fn ($pair) => $pair[0]->data()['model'])
            ->values()
            ->all();
    }

    #[Test]
    public function the_check_flags_models_in_use_that_are_deprecated_or_gone(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-old']);
        // Uses the connection's default model.
        $this->enableAiFeature(self::SUMMARIES);
        $this->offered = ['claude-old' => '2027-01-05T00:00:00Z'];

        $this->health()->check();

        $this->assertSame([
            'claude/claude-old' => ['status' => 'deprecated', 'since' => '2026-11-06T12:00:00+00:00', 'retiresAt' => '2027-01-05T00:00:00+00:00', 'features' => [self::WRITER]],
            'claude/claude-default' => ['status' => 'missing', 'since' => '2026-11-06T12:00:00+00:00', 'retiresAt' => null, 'features' => [self::SUMMARIES]],
        ], $this->issues());
    }

    #[Test]
    public function models_still_offered_and_features_turned_off_are_not_flagged(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-new']);
        $this->setting('flarum-ai.features', json_encode([
            self::WRITER => ['enabled' => true, 'model' => 'claude-new'],
            self::SUMMARIES => ['enabled' => false, 'model' => 'claude-gone'],
        ]));
        $this->offered = ['claude-new' => null];

        $this->health()->check();

        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_model_offered_again_is_no_longer_flagged(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);

        $this->health()->check();
        $this->assertSame('missing', $this->issues()['claude/claude-x']['status']);

        $this->offered = ['claude-x' => null];
        $this->health()->check();
        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_provider_that_cant_be_asked_leaves_what_was_found_before(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $health = $this->health();
        $health->check();

        $this->answer = fn () => Http::response('Down', 500);
        $health->check();

        $this->assertSame('missing', $this->issues()['claude/claude-x']['status']);
    }

    #[Test]
    public function the_check_runs_daily_and_from_the_console(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->app();
        $this->fakeProvider();

        $this->runCommand(['command' => 'ai:check-models']);

        $this->assertSame('missing', $this->issues()['claude/claude-x']['status']);

        $schedule = $this->app()->getContainer()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $this->assertNotEmpty(array_filter($schedule->events(), fn ($event) => str_contains((string) $event->command, 'ai:check-models')));
    }

    #[Test]
    public function the_admin_can_run_the_check_and_no_one_else_can(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->app();
        $this->fakeProvider();

        $check = fn (int $as) => $this->send($this->request('POST', '/api/ai/models/check', ['authenticatedAs' => $as]));

        $this->assertSame(403, $check(2)->getStatusCode());

        $response = $check(1);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('claude-x', json_decode((string) $response->getBody(), true)['issues'][0]['model']);
    }

    #[Test]
    public function the_admin_page_can_load_the_issues_again_and_no_one_else_can(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->health()->check();

        $issues = fn (int $as) => $this->send($this->request('GET', '/api/ai/models/issues', ['authenticatedAs' => $as]));

        $this->assertSame(403, $issues(2)->getStatusCode());

        $response = $issues(1);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('claude-x', json_decode((string) $response->getBody(), true)['issues'][0]['model']);
    }

    #[Test]
    public function the_admin_is_told_on_every_admin_page(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->health()->check();

        $page = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();

        $this->assertStringContainsString('"modelIssues":[{"connection":"claude","model":"claude-x","status":"missing"', $page);
    }

    #[Test]
    public function changing_a_features_model_drops_the_old_ones_warning(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->health()->check();

        $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => [
            'flarum-ai.features' => json_encode([self::WRITER => ['enabled' => true, 'model' => 'claude-y']]),
        ]]));

        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_call_to_a_features_model_that_is_gone_is_made_with_the_connections_default(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->gone = ['claude-x'];

        $response = $this->ai()->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');

        $this->assertSame('Hi from claude-default', $response->text);
        $this->assertSame(['claude-x', 'claude-default'], $this->calledModels());
        $this->assertSame(['status' => 'failed', 'since' => '2026-11-06T12:00:00+00:00', 'retiresAt' => null, 'features' => [self::WRITER]], $this->issues()['claude/claude-x']);
    }

    #[Test]
    public function a_stream_to_a_features_model_that_is_gone_is_made_with_the_connections_default(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->gone = ['claude-x'];
        $ai = $this->ai();

        $stream = $ai->feature(self::WRITER)->asSystem()->stream(agent('Be brief'), 'Hello');

        try {
            foreach ($stream as $event) {
            }
        } catch (\Throwable) {
            // The faked reply isn't a real stream; only the models asked for matter here.
        }

        $this->assertSame(['claude-x', 'claude-default'], $this->calledModels());
        $this->assertSame('failed', $this->issues()['claude/claude-x']['status']);
    }

    #[Test]
    public function when_the_default_is_gone_too_the_call_fails_and_both_are_flagged(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->enableAiFeature(self::SUMMARIES);
        $this->gone = ['claude-x', 'claude-default'];

        try {
            $this->ai()->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should have failed.');
        } catch (RequestException $e) {
            $this->assertSame(404, $e->response->status());
        }

        $this->assertSame(['claude-x', 'claude-default'], $this->calledModels());
        $this->assertSame('failed', $this->issues()['claude/claude-x']['status']);
        $this->assertSame(['status' => 'failed', 'since' => '2026-11-06T12:00:00+00:00', 'retiresAt' => null, 'features' => [self::SUMMARIES]], $this->issues()['claude/claude-default']);
    }

    #[Test]
    public function other_failures_are_not_taken_for_a_missing_model(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $ai = $this->ai();
        $this->answer = fn () => Http::response(['error' => ['message' => 'Bad request']], 400);

        try {
            $ai->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');
        } catch (RequestException) {
        }

        Http::assertSentCount(1);
        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function untagged_and_alias_names_match_what_the_provider_lists(): void
    {
        $this->aiConnection('ollama', ['driver' => 'ollama', 'url' => 'http://ollama.test'], null);
        $this->enableAiFeature(self::WRITER, ['connection' => 'ollama', 'model' => 'llama3.2']);
        $this->enableAiFeature(self::SUMMARIES, ['connection' => 'claude', 'model' => 'claude-alias']);
        $health = $this->health();

        $this->answer = fn (Request $request) => str_contains($request->url(), 'ollama.test')
            ? Http::response(['models' => [['name' => 'llama3.2:latest']]])
            : Http::response(['data' => [['id' => 'claude-alias-20261001', 'deprecated_at' => null, 'retires_at' => null]], 'has_more' => false]);

        $this->assertSame([], $health->check()['issues']);
    }

    #[Test]
    public function the_model_an_agent_chooses_itself_is_the_one_checked(): void
    {
        $this->enableAiFeature(self::WRITER);
        $this->offered = ['claude-default' => null];

        $this->ai()->feature(self::WRITER)->asSystem()->prompt(new OwnModelAgent(), 'Hello');
        $this->health()->check();

        $this->assertSame(['claude/claude-own'], array_keys($this->issues()));
        $this->assertSame([self::WRITER], $this->issues()['claude/claude-own']['features']);
    }

    #[Test]
    public function an_agents_own_model_that_is_gone_stays_flagged_after_settings_are_saved(): void
    {
        $this->enableAiFeature(self::WRITER);
        $this->gone = ['claude-own'];

        try {
            $this->ai()->feature(self::WRITER)->asSystem()->prompt(new OwnModelAgent(), 'Hello');
        } catch (RequestException) {
        }

        $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => ['flarum-ai.member_daily_tokens' => '1000']]));

        $this->assertSame('failed', $this->issues()['claude/claude-own']['status'] ?? null);
    }

    #[Test]
    public function a_connection_without_a_default_to_fall_back_to_fails_with_the_providers_error(): void
    {
        $this->aiConnection('local', ['driver' => 'openai-compatible', 'url' => 'https://llm.test/v1'], null);
        $this->enableAiFeature(self::WRITER, ['connection' => 'local', 'model' => 'local-x']);
        $ai = $this->ai();
        $this->answer = fn () => Http::response(['error' => ['message' => 'The model `local-x` does not exist', 'code' => 'model_not_found']], 404);

        try {
            $ai->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should have failed.');
        } catch (RequestException $e) {
            $this->assertSame(404, $e->response->status());
        }

        $this->assertSame('failed', $this->issues()['local/local-x']['status']);
    }

    #[Test]
    public function a_404_that_doesnt_name_the_model_is_not_taken_for_a_missing_one(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $ai = $this->ai();
        $this->answer = fn () => Http::response('<html>Not Found</html>', 404);

        try {
            $ai->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');
        } catch (RequestException) {
        }

        Http::assertSentCount(1);
        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_failure_recorded_while_the_check_runs_is_kept(): void
    {
        // A connection whose models can't be listed, so the check leaves its models be.
        $this->config('ai.connections.azure', ['driver' => 'azure', 'key' => 'k', 'url' => 'https://azure.test']);
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->enableAiFeature(self::SUMMARIES, ['connection' => 'azure', 'model' => 'azure-x']);
        $this->offered = ['claude-x' => null];
        $health = $this->health();
        $health->forAdmin();

        // Another request records a failure while this one is asking the provider.
        $this->answer = function () {
            $this->database()->table('settings')->updateOrInsert(
                ['key' => 'flarum-ai.model_issues'],
                ['value' => json_encode(['azure' => ['azure-x' => ['status' => 'failed', 'since' => '2026-11-06T11:00:00+00:00', 'retiresAt' => null]]])]
            );

            return Http::response(['data' => [['id' => 'claude-x']], 'has_more' => false]);
        };

        $result = $health->check();

        $stored = json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.model_issues')->value('value'), true);
        $this->assertSame('failed', $stored['azure']['azure-x']['status'] ?? null);
        // And the admin is shown it straight away.
        $this->assertSame(['azure/azure-x'], array_map(fn ($issue) => $issue['connection'].'/'.$issue['model'], $result['issues']));
    }

    #[Test]
    public function a_check_that_couldnt_ask_a_provider_says_so(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $health = $this->health();
        $this->answer = fn () => Http::response('Down', 500);

        $this->assertSame(['issues' => [], 'unchecked' => ['claude'], 'unlisted' => []], $health->check());
        $this->assertStringContainsString('could not be checked', $this->runCommand(['command' => 'ai:check-models']));
    }

    #[Test]
    public function a_model_failure_outside_any_feature_is_not_recorded(): void
    {
        $this->gone = ['claude-default'];
        $this->ai();

        try {
            agent('Be brief')->prompt('Hello');
        } catch (RequestException) {
        }

        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_failure_to_record_a_missing_model_doesnt_stop_the_fallback(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $this->gone = ['claude-x'];
        $ai = $this->ai();

        $health = $this->createStub(ModelHealth::class);
        $health->method('recordFailure')->willThrowException(new \RuntimeException('Database is locked'));
        $this->app()->getContainer()->instance(ModelHealth::class, $health);

        $response = $ai->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');

        $this->assertSame('Hi from claude-default', $response->text);
    }

    #[Test]
    public function the_model_an_agent_chose_is_remembered_when_the_cache_is_cleared(): void
    {
        $this->enableAiFeature(self::WRITER);
        $this->offered = ['claude-default' => null];

        $this->ai()->feature(self::WRITER)->asSystem()->prompt(new OwnModelAgent(), 'Hello');
        $this->app()->getContainer()->make(\Illuminate\Contracts\Cache\Repository::class)->flush();
        $this->health()->check();

        $this->assertSame(['claude/claude-own'], array_keys($this->issues()));
    }

    #[Test]
    public function a_model_seen_under_another_default_is_not_taken_for_the_one_in_use(): void
    {
        $this->enableAiFeature(self::WRITER);
        $this->offered = ['claude-new-default' => null];

        // Seen while the connection's default was claude-default.
        $this->ai()->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');

        $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => [
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'model' => 'claude-new-default']]),
        ]]));

        $this->assertSame([], $this->health()->check()['issues']);
    }

    #[Test]
    public function a_404_naming_a_longer_model_is_not_taken_for_this_one(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $ai = $this->ai();
        $this->answer = fn () => Http::response(['type' => 'error', 'error' => ['type' => 'not_found_error', 'message' => 'model: claude-xl']], 404);

        try {
            $ai->feature(self::WRITER)->asSystem()->prompt(agent('Be brief'), 'Hello');
        } catch (RequestException) {
        }

        Http::assertSentCount(1);
        $this->assertSame([], $this->issues());
    }

    #[Test]
    public function a_failure_recorded_for_a_model_while_it_is_checked_is_kept(): void
    {
        $this->enableAiFeature(self::WRITER, ['model' => 'claude-x']);
        $health = $this->health();
        $health->forAdmin();

        // A call finds the model gone while the provider still lists it.
        $this->answer = function () {
            $this->database()->table('settings')->updateOrInsert(
                ['key' => 'flarum-ai.model_issues'],
                ['value' => json_encode(['claude' => ['claude-x' => ['status' => 'failed', 'since' => '2026-11-06T12:00:00+00:00', 'retiresAt' => null]]])]
            );

            return Http::response(['data' => [['id' => 'claude-x']], 'has_more' => false]);
        };

        $result = $health->check();

        $this->assertSame('failed', $result['issues'][0]['status'] ?? null);
    }

    #[Test]
    public function connections_whose_models_cant_be_listed_are_reported(): void
    {
        $this->config('ai.connections.azure', ['driver' => 'azure', 'key' => 'k', 'url' => 'https://azure.test']);
        $this->enableAiFeature(self::WRITER, ['connection' => 'azure', 'model' => 'azure-x']);

        $result = $this->health()->check();

        $this->assertSame(['issues' => [], 'unchecked' => [], 'unlisted' => ['azure']], $result);
        $output = $this->runCommand(['command' => 'ai:check-models']);
        $this->assertStringContainsString('azure', $output);
        $this->assertStringNotContainsString('Every model in use is offered', $output);
    }
}

#[Model('claude-own')]
class OwnModelAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Be brief';
    }
}
