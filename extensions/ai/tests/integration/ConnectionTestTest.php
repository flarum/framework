<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Carbon\Carbon;
use Flarum\Ai\ConnectionKeys;
use Flarum\Ai\Testing\InteractsWithAi;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Testing a connection from the admin page: a short prompt whose reply is
 * streamed back, through a saved connection or one not saved yet. Provider
 * requests go through HTTP, faked.
 */
class ConnectionTestTest extends TestCase
{
    use InteractsWithAi;
    use RetrievesAuthorizedUsers;

    protected const URL = 'https://api.anthropic.test/v1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');

        $this->prepareDatabase(['users' => [$this->normalUser()]]);
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => self::URL, 'model' => 'claude-test'], 'saved-key');
    }

    protected function fakeReply(): void
    {
        $event = fn (string $type, array $data) => "event: $type\ndata: ".json_encode(['type' => $type] + $data)."\n\n";

        Http::fake(['api.anthropic.test/*' => Http::response(
            $event('message_start', ['message' => ['id' => 'msg_1', 'model' => 'claude-test', 'usage' => ['input_tokens' => 12, 'output_tokens' => 0]]])
            .$event('content_block_start', ['index' => 0, 'content_block' => ['type' => 'text', 'text' => '']])
            .$event('content_block_delta', ['index' => 0, 'delta' => ['type' => 'text_delta', 'text' => "1\n2"]])
            .$event('content_block_delta', ['index' => 0, 'delta' => ['type' => 'text_delta', 'text' => "\n3"]])
            .$event('content_block_stop', ['index' => 0])
            .$event('message_delta', ['delta' => ['stop_reason' => 'end_turn'], 'usage' => ['output_tokens' => 6]])
            .$event('message_stop', [])
        )]);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function postTest(array $body, int $as = 1): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/ai/connections/test', ['authenticatedAs' => $as, 'json' => $body]));
    }

    /**
     * Reading the body runs the test, as sending the response would.
     *
     * @return list<array<string, mixed>>
     */
    protected function events(ResponseInterface $response): array
    {
        // Not given the body as a message: casting it would run the stream outside the buffer.
        $this->assertSame(200, $response->getStatusCode());

        ob_start();
        $response->getBody()->getContents();

        return array_map(fn (string $line) => json_decode($line, true), explode("\n", trim((string) ob_get_clean())));
    }

    protected function sentToProvider(): array
    {
        $request = Http::recorded()->last()[0];

        return [$request->header('x-api-key')[0] ?? null, $request->data()['model'] ?? null, $request->url()];
    }

    #[Test]
    public function a_saved_connection_streams_its_reply(): void
    {
        $this->app();
        $this->fakeReply();

        $events = $this->events($this->postTest(['name' => 'claude']));

        $this->assertSame(['start', 'text', 'text', 'done'], array_column($events, 'type'));
        $this->assertSame("1\n2\n3", $events[1]['delta'].$events[2]['delta']);
        $this->assertSame('claude-test', $events[3]['model']);
        $this->assertSame(12, $events[3]['inputTokens']);
        $this->assertSame(6, $events[3]['outputTokens']);
        $this->assertIsInt($events[3]['firstTextMs']);
        $this->assertSame(['saved-key', 'claude-test', self::URL.'/messages'], $this->sentToProvider());
    }

    /**
     * Tests cost tokens like any call: counted for the connection, for no feature.
     */
    #[Test]
    public function a_test_is_counted_as_usage(): void
    {
        $this->app();
        $this->fakeReply();

        $this->events($this->postTest(['name' => 'claude']));

        $row = $this->database()->table('ai_usage')->first();

        $this->assertSame('claude', $row->connection);
        $this->assertNull($row->feature);
        $this->assertSame(18, (int) $row->input_tokens + (int) $row->output_tokens);
    }

    #[Test]
    public function an_unsaved_connection_is_tested_with_the_key_typed_in(): void
    {
        $this->app();
        $this->fakeReply();

        $events = $this->events($this->postTest(['name' => 'claude', 'connection' => ['driver' => 'anthropic', 'url' => self::URL, 'model' => 'claude-other', 'key' => 'typed-key']]));

        $this->assertSame('done', end($events)['type']);
        $this->assertSame(['typed-key', 'claude-other', self::URL.'/messages'], $this->sentToProvider());

        // Nothing is saved by a test.
        $this->assertSame('saved-key', $this->app()->getContainer()->make(ConnectionKeys::class)->get('claude'));
    }

    #[Test]
    public function an_unsaved_connection_keeps_the_saved_key_while_its_address_is_the_same(): void
    {
        $this->app();
        $this->fakeReply();

        $this->events($this->postTest(['name' => 'claude', 'connection' => ['driver' => 'anthropic', 'url' => self::URL.'/']]));

        $this->assertSame('saved-key', $this->sentToProvider()[0]);
    }

    #[Test]
    public function a_saved_key_is_never_sent_to_a_new_address(): void
    {
        $this->app();
        $this->fakeReply();

        $response = $this->postTest(['name' => 'claude', 'connection' => ['driver' => 'anthropic', 'url' => 'https://elsewhere.test/v1']]);

        $this->assertSame(422, $response->getStatusCode());
        Http::assertNothingSent();
    }

    #[Test]
    public function a_saved_key_is_left_out_once_removed(): void
    {
        $this->app();
        $this->fakeReply();

        $response = $this->postTest(['name' => 'claude', 'connection' => ['driver' => 'anthropic', 'url' => self::URL, 'removeKey' => true]]);

        $this->assertSame(422, $response->getStatusCode());
        Http::assertNothingSent();
    }

    #[Test]
    public function a_new_connection_can_be_tested_before_its_saved(): void
    {
        $this->app();
        $this->fakeReply();

        $events = $this->events($this->postTest(['name' => 'second', 'connection' => ['driver' => 'anthropic', 'url' => self::URL, 'model' => 'claude-test', 'key' => 'new-key']]));

        $this->assertSame('done', end($events)['type']);
        $this->assertSame('new-key', $this->sentToProvider()[0]);
    }

    #[Test]
    public function a_refused_key_is_explained(): void
    {
        $this->app();
        Http::fake(['api.anthropic.test/*' => Http::response(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key saved-key']], 401)]);

        $events = $this->events($this->postTest(['name' => 'claude']));
        $error = end($events);

        $this->assertSame('error', $error['type']);
        $this->assertSame('auth', $error['code']);
        $this->assertSame(401, $error['status']);
        // The key is never repeated back, even where the provider does.
        $this->assertSame('invalid x-api-key …', $error['message']);
    }

    #[Test]
    public function a_model_the_provider_doesnt_have_is_explained(): void
    {
        $this->app();
        Http::fake(['api.anthropic.test/*' => Http::response(['type' => 'error', 'error' => ['type' => 'not_found_error', 'message' => 'model: claude-test']], 404)]);

        $events = $this->events($this->postTest(['name' => 'claude']));

        $this->assertSame('model', end($events)['code']);
    }

    #[Test]
    public function the_monthly_limit_still_applies(): void
    {
        $this->aiConnection('claude', ['driver' => 'anthropic', 'url' => self::URL, 'model' => 'claude-test', 'monthly_tokens' => 10], 'saved-key');
        $this->prepareDatabase(['ai_usage' => [[
            'connection' => 'claude', 'driver' => 'anthropic', 'kind' => 'text',
            'input_tokens' => 8, 'output_tokens' => 4, 'created_at' => Carbon::now(),
        ]]]);
        $this->app();
        $this->fakeReply();

        $events = $this->events($this->postTest(['name' => 'claude']));

        $this->assertSame('limit', end($events)['code']);
        $this->assertSame(10, end($events)['tokens']);
        Http::assertNothingSent();
    }

    #[Test]
    public function only_admins_can_test_and_only_connections_that_exist(): void
    {
        $this->assertSame(403, $this->postTest(['name' => 'claude'], 2)->getStatusCode());
        $this->assertSame(404, $this->postTest(['name' => 'nope'])->getStatusCode());
    }

    #[Test]
    public function an_unsaved_connections_models_are_listed_with_the_key_typed_in(): void
    {
        $this->app();
        Http::fake(['llm.test/v1/models' => Http::response(['data' => [['id' => 'model-b'], ['id' => 'model-a']]])]);

        $response = $this->send($this->request('POST', '/api/ai/connections/models', [
            'authenticatedAs' => 1,
            'json' => ['name' => 'local', 'connection' => ['driver' => 'openai-compatible', 'url' => 'https://llm.test/v1', 'key' => 'typed-key']],
        ]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(['model-a', 'model-b'], array_column(json_decode((string) $response->getBody(), true)['models'], 'id'));
        $this->assertSame('Bearer typed-key', Http::recorded()->last()[0]->header('Authorization')[0]);
    }
}
