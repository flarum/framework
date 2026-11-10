<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Flarum\Ai\Models\ModelLister;
use Flarum\Ai\Testing\InteractsWithAi;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;

/**
 * The models each connection's provider offers, which the admin page lists to
 * choose from. Provider requests go through HTTP, faked.
 */
class ModelListTest extends TestCase
{
    use InteractsWithAi;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');

        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    protected function models(string $connection, array $query = [], int $as = 1): array
    {
        $response = $this->send(
            $this->request('GET', "/api/ai/connections/$connection/models", ['authenticatedAs' => $as])->withQueryParams($query)
        );

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: list<array<string, mixed>>}>
     */
    public static function providers(): array
    {
        $openAiShaped = ['data' => [['id' => 'model-b', 'object' => 'model'], ['id' => 'model-a', 'object' => 'model']]];
        $listed = [
            ['id' => 'model-a', 'name' => null, 'deprecatedAt' => null, 'retiresAt' => null],
            ['id' => 'model-b', 'name' => null, 'deprecatedAt' => null, 'retiresAt' => null],
        ];

        return [
            'OpenAI' => [['driver' => 'openai', 'url' => 'https://api.openai.test/v1'], ['api.openai.test/v1/models' => $openAiShaped], $listed],
            'OpenAI, with a shutdown date' => [
                ['driver' => 'openai', 'url' => 'https://api.openai.test/v1'],
                ['api.openai.test/v1/models' => ['data' => [['id' => 'gpt-old', 'shutdown_date' => '2027-02-01']]]],
                [['id' => 'gpt-old', 'name' => null, 'deprecatedAt' => null, 'retiresAt' => '2027-02-01T00:00:00+00:00']],
            ],
            'DeepSeek' => [['driver' => 'deepseek', 'url' => 'https://api.deepseek.test/v1'], ['api.deepseek.test/v1/models' => $openAiShaped], $listed],
            'Groq' => [['driver' => 'groq', 'url' => 'https://api.groq.test/openai/v1'], ['api.groq.test/openai/v1/models' => $openAiShaped], $listed],
            'Mistral' => [['driver' => 'mistral', 'url' => 'https://api.mistral.test/v1'], ['api.mistral.test/v1/models' => $openAiShaped], $listed],
            'xAI' => [['driver' => 'xai', 'url' => 'https://api.x.test/v1'], ['api.x.test/v1/models' => $openAiShaped], $listed],
            'OpenAI-compatible' => [['driver' => 'openai-compatible', 'url' => 'https://llm.test/v1'], ['llm.test/v1/models' => $openAiShaped], $listed],
            'OpenRouter' => [
                ['driver' => 'openrouter', 'url' => 'https://openrouter.test/api/v1'],
                ['openrouter.test/api/v1/models' => ['data' => [
                    ['id' => 'acme/old', 'name' => 'Acme: Old', 'expiration_date' => '2026-12-01'],
                    ['id' => 'acme/new', 'name' => 'Acme: New', 'expiration_date' => null],
                ]]],
                [
                    ['id' => 'acme/new', 'name' => 'Acme: New', 'deprecatedAt' => null, 'retiresAt' => null],
                    ['id' => 'acme/old', 'name' => 'Acme: Old', 'deprecatedAt' => null, 'retiresAt' => '2026-12-01T00:00:00+00:00'],
                ],
            ],
            'Anthropic, over two pages' => [
                ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1'],
                [
                    'api.anthropic.test/v1/models?limit=1000' => ['data' => [
                        ['id' => 'claude-old', 'display_name' => 'Claude Old', 'deprecated_at' => '2026-09-01T00:00:00Z', 'retires_at' => '2027-01-05T00:00:00Z'],
                    ], 'has_more' => true, 'last_id' => 'claude-old'],
                    'api.anthropic.test/v1/models?limit=1000&after_id=claude-old' => ['data' => [
                        ['id' => 'claude-new', 'display_name' => 'Claude New', 'deprecated_at' => null, 'retires_at' => null],
                    ], 'has_more' => false, 'last_id' => 'claude-new'],
                ],
                [
                    ['id' => 'claude-new', 'name' => 'Claude New', 'deprecatedAt' => null, 'retiresAt' => null],
                    ['id' => 'claude-old', 'name' => 'Claude Old', 'deprecatedAt' => '2026-09-01T00:00:00+00:00', 'retiresAt' => '2027-01-05T00:00:00+00:00'],
                ],
            ],
            'Gemini, text models only, over two pages' => [
                ['driver' => 'gemini', 'url' => 'https://gemini.test/v1beta'],
                [
                    'gemini.test/v1beta/models?pageSize=1000' => ['models' => [
                        ['name' => 'models/gemini-pro', 'displayName' => 'Gemini Pro', 'supportedGenerationMethods' => ['generateContent', 'countTokens']],
                        ['name' => 'models/text-embedding', 'displayName' => 'Embedding', 'supportedGenerationMethods' => ['embedContent']],
                    ], 'nextPageToken' => 'page2'],
                    'gemini.test/v1beta/models?pageSize=1000&pageToken=page2' => ['models' => [
                        ['name' => 'models/gemini-flash', 'displayName' => 'Gemini Flash', 'supportedGenerationMethods' => ['generateContent']],
                    ]],
                ],
                [
                    ['id' => 'gemini-flash', 'name' => 'Gemini Flash', 'deprecatedAt' => null, 'retiresAt' => null],
                    ['id' => 'gemini-pro', 'name' => 'Gemini Pro', 'deprecatedAt' => null, 'retiresAt' => null],
                ],
            ],
            'Ollama' => [
                ['driver' => 'ollama', 'url' => 'http://ollama.test:11434'],
                ['ollama.test:11434/api/tags' => ['models' => [['name' => 'llama3.2:latest'], ['name' => 'gemma:2b']]]],
                [
                    ['id' => 'gemma:2b', 'name' => null, 'deprecatedAt' => null, 'retiresAt' => null],
                    ['id' => 'llama3.2:latest', 'name' => null, 'deprecatedAt' => null, 'retiresAt' => null],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providers')]
    public function each_providers_models_are_listed(array $connection, array $responses, array $expected): void
    {
        $this->aiConnection('main', $connection, 'test-key');
        $this->app();

        Http::fake(function (Request $request) use ($responses) {
            $url = preg_replace('#^https?://#', '', $request->url());

            return isset($responses[$url]) ? Http::response($responses[$url]) : Http::response(['error' => "Unexpected $url"], 500);
        });

        [$status, $body] = $this->models('main');

        $this->assertSame(200, $status, json_encode($body));
        $this->assertSame($expected, $body['models']);
        $this->assertNotNull($body['checkedAt']);
    }

    #[Test]
    public function the_connections_key_is_sent_to_its_provider_and_not_returned(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1'], 'sk-ant-secret');
        $this->app();
        Http::fake(['*' => Http::response(['data' => [], 'has_more' => false])]);

        [$status, $body] = $this->models('claude');

        $this->assertSame(200, $status);
        Http::assertSent(fn (Request $request) => $request->hasHeader('x-api-key', 'sk-ant-secret'));
        $this->assertStringNotContainsString('sk-ant-secret', json_encode($body));
    }

    #[Test]
    public function lists_are_kept_until_the_admin_asks_again(): void
    {
        $this->aiConnection('ollama', ['driver' => 'ollama', 'url' => 'http://ollama.test'], null);
        $this->app();
        Http::fake(['*' => Http::sequence()
            ->push(['models' => [['name' => 'first']]])
            ->push(['models' => [['name' => 'second']]])]);

        $this->assertSame('first', $this->models('ollama')[1]['models'][0]['id']);
        $this->assertSame('first', $this->models('ollama')[1]['models'][0]['id']);
        Http::assertSentCount(1);

        $this->assertSame('second', $this->models('ollama', ['refresh' => '1'])[1]['models'][0]['id']);
    }

    #[Test]
    public function a_provider_that_cant_be_listed_says_so(): void
    {
        $this->config('ai.connections.azure', ['driver' => 'azure', 'key' => 'k', 'url' => 'https://azure.test']);
        $this->app();
        Http::fake();

        [$status, $body] = $this->models('azure');

        $this->assertSame(200, $status);
        $this->assertNull($body['models']);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_provider_that_fails_is_reported(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']);
        $this->app();
        Http::fake(['*' => Http::response(['error' => ['message' => 'invalid x-api-key']], 401)]);

        [$status, $body] = $this->models('claude');

        $this->assertSame(502, $status);
        $this->assertSame('ai_models_unavailable', $body['errors'][0]['code']);
    }

    #[Test]
    public function only_admins_can_list_models_and_only_for_connections_that_exist(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']);
        $this->app();
        Http::fake(['*' => Http::response(['data' => [], 'has_more' => false])]);

        $this->assertSame(403, $this->models('claude', [], 2)[0]);
        $this->assertSame(404, $this->models('missing')[0]);
        Http::assertNothingSent();
    }

    #[Test]
    public function bedrocks_models_and_inference_profiles_are_listed_with_their_lifecycle(): void
    {
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1']]));
        $this->prepareDatabase(['ai_connection_keys' => [['connection' => 'aws', 'key' => 'ABSKsecret', 'updated_at' => date('Y-m-d H:i:s')]]]);

        $requests = [];
        $aws = new MockHandler([
            new Result(['modelSummaries' => [
                [
                    'modelId' => 'anthropic.claude-old-v1:0', 'modelName' => 'Claude Old', 'inferenceTypesSupported' => ['ON_DEMAND'],
                    'modelLifecycle' => ['status' => 'LEGACY', 'legacyTime' => '2026-09-01T00:00:00Z', 'endOfLifeTime' => '2027-03-01T00:00:00Z'],
                ],
                [
                    'modelId' => 'anthropic.claude-new-v1:0', 'modelName' => 'Claude New', 'inferenceTypesSupported' => ['INFERENCE_PROFILE'],
                    'modelLifecycle' => ['status' => 'ACTIVE'],
                ],
                [
                    'modelId' => 'amazon.nova-pro-v1:0', 'modelName' => 'Nova Pro', 'inferenceTypesSupported' => ['ON_DEMAND', 'INFERENCE_PROFILE'],
                    'modelLifecycle' => ['status' => 'ACTIVE'],
                ],
            ]]),
            new Result([
                'inferenceProfileSummaries' => [[
                    'inferenceProfileId' => 'eu.anthropic.claude-new-v1:0', 'inferenceProfileName' => 'EU Claude New', 'status' => 'ACTIVE',
                    'models' => [['modelArn' => 'arn:aws:bedrock:eu-central-1::foundation-model/anthropic.claude-new-v1:0']],
                ]],
                'nextToken' => 'page2',
            ]),
            new Result(['inferenceProfileSummaries' => [[
                'inferenceProfileId' => 'eu.anthropic.claude-old-v1:0', 'inferenceProfileName' => 'EU Claude Old', 'status' => 'ACTIVE',
                'models' => [['modelArn' => 'arn:aws:bedrock:eu-central-1::foundation-model/anthropic.claude-old-v1:0']],
            ]]]),
        ], function () {}, function () {});

        $this->app()->getContainer()->instance(ModelLister::class, new ModelLister(function (CommandInterface $command, RequestInterface $request) use ($aws, &$requests) {
            $requests[] = [$command->getName(), $command->toArray(), $request->getHeaderLine('Authorization')];

            return $aws($command, $request);
        }));

        [$status, $body] = $this->models('aws');

        $this->assertSame(200, $status, json_encode($body));
        $this->assertSame([
            ['id' => 'amazon.nova-pro-v1:0', 'name' => 'Nova Pro', 'deprecatedAt' => null, 'retiresAt' => null],
            ['id' => 'anthropic.claude-old-v1:0', 'name' => 'Claude Old', 'deprecatedAt' => '2026-09-01T00:00:00+00:00', 'retiresAt' => '2027-03-01T00:00:00+00:00'],
            ['id' => 'eu.anthropic.claude-new-v1:0', 'name' => 'EU Claude New', 'deprecatedAt' => null, 'retiresAt' => null],
            ['id' => 'eu.anthropic.claude-old-v1:0', 'name' => 'EU Claude Old', 'deprecatedAt' => '2026-09-01T00:00:00+00:00', 'retiresAt' => '2027-03-01T00:00:00+00:00'],
        ], $body['models']);

        $this->assertSame(['ListFoundationModels', 'ListInferenceProfiles', 'ListInferenceProfiles'], array_column($requests, 0));
        $this->assertSame('TEXT', $requests[0][1]['byOutputModality']);
        $this->assertSame('SYSTEM_DEFINED', $requests[1][1]['typeEquals']);
        $this->assertSame('page2', $requests[2][1]['nextToken']);
        $this->assertSame('Bearer ABSKsecret', $requests[0][2]);
    }

    protected function bedrockLister(array &$requests): ModelLister
    {
        return new ModelLister(function (CommandInterface $command, RequestInterface $request) use (&$requests) {
            $requests[] = [$command->getName(), $request->getHeaderLine('X-Proxy-Token'), $request->getUri()->getHost()];

            return new Result($command->getName() === 'ListFoundationModels' ? ['modelSummaries' => []] : ['inferenceProfileSummaries' => []]);
        });
    }

    #[Test]
    public function bedrocks_list_is_asked_with_the_connections_headers(): void
    {
        $this->config('ai.connections.host', ['driver' => 'bedrock', 'region' => 'eu-west-1', 'key' => 'ABSKsecret', 'headers' => ['X-Proxy-Token' => 'abc']]);
        $requests = [];
        $this->app()->getContainer()->instance(ModelLister::class, $this->bedrockLister($requests));

        $this->assertSame(200, $this->models('host')[0]);
        $this->assertSame('abc', $requests[0][1]);
    }

    #[Test]
    public function a_bedrock_list_is_asked_again_once_the_region_changes(): void
    {
        $this->config('ai.bedrock_server_credentials', true);
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1']]));
        $this->prepareDatabase(['ai_connection_keys' => [['connection' => 'aws', 'key' => 'ABSKsecret', 'updated_at' => date('Y-m-d H:i:s')]]]);
        $requests = [];
        $this->app()->getContainer()->instance(ModelLister::class, $this->bedrockLister($requests));

        $this->models('aws');

        $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => 1, 'json' => [
            'flarum-ai.connections' => json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1', 'key' => 'ABSKsecret']]),
        ]]));

        $this->models('aws');

        $this->assertSame(['bedrock.eu-central-1.amazonaws.com', 'bedrock.us-east-1.amazonaws.com'], array_values(array_unique(array_column($requests, 2))));
    }
}
