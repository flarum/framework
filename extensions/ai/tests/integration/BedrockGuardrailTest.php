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
use Flarum\Testing\integration\TestCase;
use Generator;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Promptable;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

use function Laravel\Ai\agent;

/**
 * The guardrail a Bedrock connection is set up with, applied to every text
 * call made through it. Bedrock's requests go through the AWS SDK, so its
 * gateway is swapped for one that records what it would have sent.
 */
class BedrockGuardrailTest extends TestCase
{
    use InteractsWithAi;

    protected RecordingGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
        $this->extend((new AiExtender())->feature('test-ext.writer', 'test-ext.writer_label'));
        // Keyless connections, which never reach AWS here.
        $this->config('ai.bedrock_server_credentials', true);

        $this->setting('flarum-ai.connections', json_encode([
            'aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'guardrail_id' => 'gr-abc', 'guardrail_version' => '3'],
            'plain' => ['driver' => 'bedrock', 'region' => 'eu-central-1'],
        ]));
        $this->setting('flarum-ai.default_connection', 'aws');
    }

    protected function record(string $connection): void
    {
        $this->gateway = new RecordingGateway();
        $this->app()->getContainer()->make(AiManager::class)->instance($connection)->useTextGateway($this->gateway);
    }

    protected function sent(callable $call): ?TextGenerationOptions
    {
        try {
            $call();
        } catch (RuntimeException $e) {
            $this->assertSame('Recorded', $e->getMessage());
        }

        return $this->gateway->options;
    }

    protected const GUARDRAIL = ['guardrailIdentifier' => 'gr-abc', 'guardrailVersion' => '3'];

    #[Test]
    public function every_call_through_the_connection_is_guarded(): void
    {
        $this->record('aws');

        $options = $this->sent(fn () => agent('Be brief')->prompt('Hello', provider: 'aws'));

        $this->assertSame(self::GUARDRAIL, $options->providerOptions(Lab::Bedrock)['guardrailConfig']);
    }

    #[Test]
    public function streams_are_guarded_too(): void
    {
        $this->record('aws');

        $options = $this->sent(function () {
            foreach (agent('Be brief')->stream('Hello', provider: 'aws') as $event) {
            }
        });

        $this->assertSame(self::GUARDRAIL, $options->providerOptions(Lab::Bedrock)['guardrailConfig']);
    }

    #[Test]
    public function feature_calls_are_guarded_too_alongside_their_own_settings(): void
    {
        $this->enableAiFeature('test-ext.writer', ['temperature' => 0.2]);
        $this->record('aws');
        $ai = $this->app()->getContainer()->make(Ai::class);

        $options = $this->sent(fn () => $ai->feature('test-ext.writer')->asSystem()->prompt(agent('Be brief'), 'Hello'));

        $this->assertSame(self::GUARDRAIL, $options->providerOptions(Lab::Bedrock)['guardrailConfig']);
        $this->assertSame(0.2, $options->temperature);
    }

    #[Test]
    public function the_admins_guardrail_wins_over_an_agents_own_and_keeps_its_other_options(): void
    {
        $this->record('aws');

        $options = $this->sent(fn () => (new OwnGuardrailAgent())->prompt('Hello', provider: 'aws'));

        $this->assertSame(self::GUARDRAIL, $options->providerOptions(Lab::Bedrock)['guardrailConfig']);
        $this->assertSame(['maxTokens' => 10], $options->providerOptions(Lab::Bedrock)['additionalModelRequestFields']);
    }

    #[Test]
    public function a_connection_without_a_guardrail_sends_none(): void
    {
        $this->record('plain');

        $unreached = $this->gateway->options = new TextGenerationOptions();

        $options = $this->sent(fn () => agent('Be brief')->prompt('Hello', provider: 'plain'));

        $this->assertNotSame($unreached, $options, 'The call never reached the gateway.');
        $this->assertArrayNotHasKey('guardrailConfig', $options?->providerOptions(Lab::Bedrock) ?? []);
    }
}

class OwnGuardrailAgent implements Agent, HasProviderOptions
{
    use Promptable;

    public function instructions(): string
    {
        return 'Be brief';
    }

    public function providerOptions(Lab|string $provider): array
    {
        return ['guardrailConfig' => ['guardrailIdentifier' => 'agents-own'], 'additionalModelRequestFields' => ['maxTokens' => 10]];
    }
}

class RecordingGateway implements StepTextGateway
{
    public ?TextGenerationOptions $options = null;

    public function generateTextStep(TextProvider $provider, string $model, ?string $instructions, array $messages, array $tools, ?array $schema, ?TextGenerationOptions $options, ?int $timeout, StepContext $stepContext): StepResponse
    {
        $this->options = $options;

        throw new RuntimeException('Recorded');
    }

    public function generateStreamStep(string $invocationId, TextProvider $provider, string $model, ?string $instructions, array $messages, array $tools, ?array $schema, ?TextGenerationOptions $options, ?int $timeout, StepContext $stepContext): Generator
    {
        $this->options = $options;

        throw new RuntimeException('Recorded');
    }
}
