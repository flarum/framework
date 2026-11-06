<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Flarum\Ai\Ai;
use Flarum\Ai\Extend\Ai as AiExtender;
use Flarum\Ai\Testing\InteractsWithAi;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use PHPUnit\Framework\Attributes\Test;

use function Laravel\Ai\agent;

/**
 * The temperature and longest reply the admin sets for a feature, sent with
 * every call it makes, whatever agent the extension uses.
 */
class FeatureOptionsTest extends TestCase
{
    use InteractsWithAi;
    use RetrievesAuthorizedUsers;

    protected const FEATURE = 'test-ext.writer';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
        $this->extend((new AiExtender())->feature(self::FEATURE, 'test-ext.writer_label'));

        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']);
        $this->grantAiFeature(self::FEATURE, Group::MEMBER_ID);
    }

    protected function ai(): Ai
    {
        $ai = $this->app()->getContainer()->make(Ai::class);

        Http::fake(['*.test/*' => Http::response([
            'id' => 'msg_1',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-test',
            'content' => [['type' => 'text', 'text' => 'Hi']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 12, 'output_tokens' => 5],
        ])]);

        return $ai;
    }

    /**
     * @return array{temperature: mixed, max_tokens: mixed}
     */
    protected function sentOptions(): array
    {
        $data = Http::recorded()->last()[0]->data();

        return ['temperature' => $data['temperature'] ?? null, 'max_tokens' => $data['max_tokens'] ?? null];
    }

    protected function save(array $settings): int
    {
        return $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => $settings]))->getStatusCode();
    }

    protected function storedFeature(): array
    {
        return (json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.features')->value('value'), true) ?? [])[self::FEATURE] ?? [];
    }

    #[Test]
    public function a_features_temperature_and_max_tokens_are_sent_with_its_calls(): void
    {
        $this->enableAiFeature(self::FEATURE, ['temperature' => 0.3, 'max_tokens' => 256]);

        $this->ai()->feature(self::FEATURE)->as(User::query()->find(2))->prompt(agent('Be brief'), 'Hello');

        $this->assertSame(['temperature' => 0.3, 'max_tokens' => 256], $this->sentOptions());
    }

    #[Test]
    public function the_admins_settings_win_over_the_agents_own(): void
    {
        $this->enableAiFeature(self::FEATURE, ['temperature' => 0.3]);

        $this->ai()->feature(self::FEATURE)->asSystem()->prompt(new TunedAgent(), 'Hello');

        // The agent's own max tokens still apply where the admin set none.
        $this->assertSame(['temperature' => 0.3, 'max_tokens' => 100], $this->sentOptions());
    }

    #[Test]
    public function without_settings_the_agents_own_are_used(): void
    {
        $this->enableAiFeature(self::FEATURE);

        $this->ai()->feature(self::FEATURE)->asSystem()->prompt(new TunedAgent(), 'Hello');

        $this->assertSame(['temperature' => 0.9, 'max_tokens' => 100], $this->sentOptions());
    }

    #[Test]
    public function streams_use_them_too(): void
    {
        $this->enableAiFeature(self::FEATURE, ['temperature' => 0.3, 'max_tokens' => 256]);

        // Faked here, not in ai(): the first HTTP fake that matches answers.
        $ai = $this->app()->getContainer()->make(Ai::class);
        Http::fake(['*.test/*' => Http::response(
            "event: message_start\ndata: {\"type\":\"message_start\",\"message\":{\"id\":\"msg_1\",\"model\":\"claude-test\",\"usage\":{\"input_tokens\":12,\"output_tokens\":0}}}\n\n"
            ."event: message_stop\ndata: {\"type\":\"message_stop\"}\n\n"
        )]);

        $stream = $ai->feature(self::FEATURE)->asSystem()->stream(agent('Be brief'), 'Hello');
        foreach ($stream as $event) {
        }

        $this->assertSame(['temperature' => 0.3, 'max_tokens' => 256], $this->sentOptions());
    }

    #[Test]
    public function calls_outside_a_feature_are_left_alone(): void
    {
        $this->enableAiFeature(self::FEATURE, ['temperature' => 0.3, 'max_tokens' => 256]);
        $this->ai();

        (new TunedAgent())->prompt('Hello');

        $this->assertSame(['temperature' => 0.9, 'max_tokens' => 100], $this->sentOptions());
    }

    #[Test]
    public function they_are_validated_when_saved(): void
    {
        $save = fn (array $settings) => $this->save(['flarum-ai.features' => json_encode([self::FEATURE => ['enabled' => true] + $settings])]);

        $this->assertSame(204, $save(['temperature' => '0.7', 'max_tokens' => '2048']));
        $this->assertSame(['enabled' => true, 'temperature' => 0.7, 'max_tokens' => 2048], $this->storedFeature());

        $this->assertSame(204, $save(['temperature' => 0, 'max_tokens' => '']));
        $this->assertSame(['enabled' => true, 'temperature' => 0.0], $this->storedFeature());

        $this->assertSame(204, $save(['temperature' => '']));
        $this->assertSame(['enabled' => true], $this->storedFeature());

        $this->assertSame(422, $save(['temperature' => 2.5]));
        $this->assertSame(422, $save(['temperature' => -0.1]));
        $this->assertSame(422, $save(['temperature' => 'warm']));
        $this->assertSame(422, $save(['max_tokens' => 0]));
        $this->assertSame(422, $save(['max_tokens' => 1.5]));
    }

    /**
     * A provider whose model takes no temperature, as current Claude models
     * don't: Anthropic documents a 400, though not its exact wording.
     */
    protected function fakeModelWithoutTemperature(): void
    {
        Http::fake(fn (Request $request) => array_key_exists('temperature', $request->data())
            ? Http::response(['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => 'temperature: This model does not support setting temperature.']], 400)
            : Http::response([
                'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-test',
                'content' => [['type' => 'text', 'text' => 'Hi']], 'stop_reason' => 'end_turn',
                'usage' => ['input_tokens' => 12, 'output_tokens' => 5],
            ]));
    }

    #[Test]
    public function a_model_that_takes_no_temperature_is_called_without_one_from_then_on(): void
    {
        $this->enableAiFeature(self::FEATURE, ['model' => 'claude-new', 'temperature' => 0.3]);
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeModelWithoutTemperature();

        $this->assertSame('Hi', $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello')->text);
        $this->assertSame([true, false], Http::recorded()->map(fn ($pair) => array_key_exists('temperature', $pair[0]->data()))->all());

        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello again');
        $this->assertSame([true, false, false], Http::recorded()->map(fn ($pair) => array_key_exists('temperature', $pair[0]->data()))->all());

        $page = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();
        $this->assertStringContainsString('"temperatureIgnored":[{"feature":"test-ext.writer","connection":"claude","model":"claude-new"}]', $page);
    }

    #[Test]
    public function a_refusal_is_remembered_for_the_feature_it_came_from(): void
    {
        // A model can refuse a temperature only for some calls, such as ones
        // that think first, so another feature's calls still send theirs.
        $this->extend((new AiExtender())->feature('test-ext.other', 'test-ext.other_label'));
        $this->enableAiFeature(self::FEATURE, ['model' => 'claude-new', 'temperature' => 0.3]);
        $this->enableAiFeature('test-ext.other', ['model' => 'claude-new', 'temperature' => 0.5]);
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeModelWithoutTemperature();

        $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
        $ai->feature('test-ext.other')->asSystem()->prompt(agent('Be brief'), 'Hello');

        // The other feature sends its temperature, and learns for itself.
        $this->assertSame([true, false, true, false], Http::recorded()->map(fn ($pair) => array_key_exists('temperature', $pair[0]->data()))->all());
    }

    #[Test]
    public function a_temperature_out_of_the_models_range_is_not_taken_for_one_it_refuses(): void
    {
        $this->enableAiFeature(self::FEATURE, ['model' => 'claude-new', 'temperature' => 1.5]);
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeModelWithoutTemperature();

        try {
            $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should have failed.');
        } catch (RequestException $e) {
            $this->assertSame(400, $e->response->status());
        }

        Http::assertSentCount(1);

        $page = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();
        $this->assertStringContainsString('"temperatureIgnored":[]', $page);
    }

    #[Test]
    public function an_agents_own_temperature_is_left_out_for_a_model_that_refuses_one(): void
    {
        $this->enableAiFeature(self::FEATURE, ['model' => 'claude-new']);
        $ai = $this->app()->getContainer()->make(Ai::class);
        $this->fakeModelWithoutTemperature();

        $this->assertSame('Hi', $ai->feature(self::FEATURE)->asSystem()->prompt(new TunedAgent(), 'Hello')->text);
        $this->assertSame([true, false], Http::recorded()->map(fn ($pair) => array_key_exists('temperature', $pair[0]->data()))->all());
    }

    #[Test]
    public function other_bad_requests_are_not_taken_for_a_temperature_the_model_refuses(): void
    {
        $this->enableAiFeature(self::FEATURE, ['temperature' => 0.3]);
        $ai = $this->app()->getContainer()->make(Ai::class);
        Http::fake(['*' => Http::response(['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => 'messages: field required']], 400)]);

        try {
            $ai->feature(self::FEATURE)->asSystem()->prompt(agent('Be brief'), 'Hello');
            $this->fail('The call should have failed.');
        } catch (RequestException $e) {
            $this->assertSame(400, $e->response->status());
        }

        Http::assertSentCount(1);
    }
}

#[Temperature(0.9)]
#[MaxTokens(100)]
class TunedAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Be brief';
    }
}
