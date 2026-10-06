<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Flarum\Ai\NotConfiguredException;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\AnonymousAgent;
use PHPUnit\Framework\Attributes\Test;

use function Laravel\Ai\agent;

/**
 * Plain Laravel AI SDK calls, made from inside Flarum, reach the provider the
 * admin or config.php configured. Requests go through the SDK's real gateway
 * code, with HTTP faked.
 */
class ConnectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
    }

    protected function anthropic(string $key): array
    {
        return ['driver' => 'anthropic', 'key' => $key, 'url' => 'https://api.anthropic.test/v1'];
    }

    protected function useAdminConnection(string $key): void
    {
        $this->setting('flarum-ai.connections', json_encode(['claude' => $this->anthropic($key)]));
        $this->setting('flarum-ai.default_connection', 'claude');
    }

    protected function fakeAnthropic(): void
    {
        Http::fake(['api.anthropic.test/*' => Http::response([
            'id' => 'msg_1',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-test',
            'content' => [['type' => 'text', 'text' => 'Hi from the provider']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 12, 'output_tokens' => 5],
        ])]);
    }

    protected function prompt(): string
    {
        return agent('Be brief')->prompt('Hello')->text;
    }

    protected function keySentToProvider(): ?string
    {
        return Http::recorded()->last()[0]->header('x-api-key')[0] ?? null;
    }

    #[Test]
    public function prompts_go_to_the_admins_default_connection(): void
    {
        $this->useAdminConnection('admin-key');
        $this->app();
        $this->fakeAnthropic();

        $this->assertSame('Hi from the provider', $this->prompt());
        $this->assertSame('admin-key', $this->keySentToProvider());
    }

    #[Test]
    public function config_php_overrides_the_admins_connection(): void
    {
        $this->useAdminConnection('admin-key');
        $this->config('ai.connections.claude', $this->anthropic('host-key'));
        $this->app();
        $this->fakeAnthropic();

        $this->prompt();

        $this->assertSame('host-key', $this->keySentToProvider());
    }

    #[Test]
    public function config_php_can_choose_the_default_connection(): void
    {
        $this->useAdminConnection('admin-key');
        $this->config('ai', [
            'default_connection' => 'host',
            'connections' => ['host' => $this->anthropic('host-key')],
        ]);
        $this->app();
        $this->fakeAnthropic();

        $this->prompt();

        $this->assertSame('host-key', $this->keySentToProvider());
    }

    #[Test]
    public function prompting_with_no_connection_says_what_is_missing(): void
    {
        $this->app();

        $this->expectException(NotConfiguredException::class);

        $this->prompt();
    }

    #[Test]
    public function saving_new_settings_takes_effect_without_a_restart(): void
    {
        $this->useAdminConnection('old-key');
        $this->app();
        $this->fakeAnthropic();

        $this->prompt();
        $this->assertSame('old-key', $this->keySentToProvider());

        $response = $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['flarum-ai.connections' => json_encode(['claude' => $this->anthropic('new-key')])],
        ]));
        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());

        $this->prompt();
        $this->assertSame('new-key', $this->keySentToProvider());
    }

    #[Test]
    public function the_sdks_fakes_work(): void
    {
        $this->app();

        AnonymousAgent::fake(['Faked reply']);

        $this->assertSame('Faked reply', $this->prompt());
        AnonymousAgent::assertPrompted('Hello');
    }
}
