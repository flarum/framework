<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Connections set up on the admin page. API keys are write-only: the admin
 * page is never sent one, and saving a connection without a key keeps the
 * one already stored.
 */
class AdminConnectionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected const KEY = 'sk-ant-secret-0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);
    }

    protected function store(array $connections, ?string $default = null): void
    {
        $this->setting('flarum-ai.connections', json_encode($connections));

        if ($default !== null) {
            $this->setting('flarum-ai.default_connection', $default);
        }
    }

    protected function adminPage(): string
    {
        $response = $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    protected function payload(string $page): array
    {
        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', $page, $matches);

        $this->assertNotEmpty($matches, 'The admin page carried no JSON payload.');

        return json_decode(html_entity_decode($matches[1]), true);
    }

    protected function save(array $settings, int $actor = 1): int
    {
        return $this->send(
            $this->request('POST', '/api/settings', ['authenticatedAs' => $actor, 'json' => $settings])
        )->getStatusCode();
    }

    protected function stored(): array
    {
        return json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.connections')->value('value'), true) ?? [];
    }

    protected function storedDefault(): ?string
    {
        return $this->database()->table('settings')->where('key', 'flarum-ai.default_connection')->value('value');
    }

    #[Test]
    public function the_admin_page_is_never_sent_a_key(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic', 'key' => self::KEY, 'url' => 'https://api.anthropic.test/v1']]);

        $page = $this->adminPage();

        $this->assertStringNotContainsString(self::KEY, $page);
        $this->assertSame(
            ['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1', 'hasKey' => true]],
            json_decode($this->payload($page)['settings']['flarum-ai.connections'], true)
        );
    }

    #[Test]
    public function config_php_connections_are_shown_without_their_keys(): void
    {
        $this->config('ai', [
            'default_connection' => 'host',
            'connections' => ['host' => ['driver' => 'azure', 'key' => self::KEY, 'url' => 'https://example.test']],
        ]);

        $page = $this->adminPage();
        $payload = $this->payload($page)['flarum-ai'];

        $this->assertStringNotContainsString(self::KEY, $page);
        $this->assertSame(['host' => ['driver' => 'azure', 'url' => 'https://example.test', 'hasKey' => true]], $payload['lockedConnections']);
        $this->assertSame('host', $payload['lockedDefault']);
        $this->assertContains('anthropic', $payload['drivers']);
    }

    #[Test]
    public function a_new_connection_is_saved_with_its_key(): void
    {
        $status = $this->save([
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'key' => ' '.self::KEY.' ']]),
            'flarum-ai.default_connection' => 'claude',
        ]);

        $this->assertSame(204, $status);
        $this->assertSame(['claude' => ['driver' => 'anthropic', 'key' => self::KEY]], $this->stored());
        $this->assertSame('claude', $this->storedDefault());
    }

    #[Test]
    public function a_connection_saved_without_a_key_keeps_the_stored_one(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic', 'key' => self::KEY]]);

        $status = $this->save([
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://proxy.test/v1', 'hasKey' => true]]),
        ]);

        $this->assertSame(204, $status);
        $this->assertSame(['claude' => ['driver' => 'anthropic', 'url' => 'https://proxy.test/v1', 'key' => self::KEY]], $this->stored());
    }

    #[Test]
    public function an_empty_key_removes_the_stored_one(): void
    {
        $this->store(['local' => ['driver' => 'ollama', 'key' => self::KEY]]);

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama', 'key' => '']])]));
        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());
    }

    #[Test]
    public function a_key_is_not_kept_for_a_different_provider(): void
    {
        $this->store(['main' => ['driver' => 'anthropic', 'key' => self::KEY]]);

        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['main' => ['driver' => 'openai']])]));
        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['main' => ['driver' => 'openai', 'key' => 'sk-openai']])]));
        $this->assertSame(['main' => ['driver' => 'openai', 'key' => 'sk-openai']], $this->stored());
    }

    #[Test]
    public function removing_a_connection_removes_its_key(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic', 'key' => self::KEY], 'local' => ['driver' => 'ollama']]);

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama']])]));
        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());

        // Adding it back under the same name doesn't bring the old key back.
        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic']])]));
    }

    public static function invalidConnections(): array
    {
        return [
            'not an object' => ['["claude"]'],
            'not json' => ['{claude'],
            'name with a space' => [json_encode(['my claude' => ['driver' => 'anthropic', 'key' => 'k']])],
            'reserved name' => [json_encode(['flarum-ai-unconfigured' => ['driver' => 'anthropic', 'key' => 'k']])],
            'provider the page does not offer' => [json_encode(['claude' => ['driver' => 'bedrock', 'key' => 'k']])],
            'no provider' => [json_encode(['claude' => ['key' => 'k']])],
            'url that is not http' => [json_encode(['claude' => ['driver' => 'anthropic', 'key' => 'k', 'url' => 'file:///etc/passwd']])],
            'missing key' => [json_encode(['claude' => ['driver' => 'anthropic']])],
            'compatible server without a url' => [json_encode(['own' => ['driver' => 'openai-compatible']])],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConnections')]
    public function invalid_connections_are_rejected_and_nothing_is_saved(string $connections): void
    {
        $this->store(['local' => ['driver' => 'ollama']], 'local');

        $this->assertSame(422, $this->save([
            'flarum-ai.connections' => $connections,
            'flarum-ai.default_connection' => '',
            'forum_title' => 'Changed',
        ]));

        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());
        $this->assertSame('local', $this->storedDefault());
        $this->assertNotSame('Changed', $this->database()->table('settings')->where('key', 'forum_title')->value('value'));
    }

    #[Test]
    public function a_name_config_php_uses_is_rejected(): void
    {
        $this->config('ai.connections.host', ['driver' => 'anthropic', 'key' => 'host-key']);

        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['host' => ['driver' => 'anthropic', 'key' => 'k']])]));
    }

    #[Test]
    public function the_default_must_name_a_connection(): void
    {
        $this->store(['local' => ['driver' => 'ollama']]);
        $this->config('ai.connections.host', ['driver' => 'anthropic', 'key' => 'host-key']);

        $this->assertSame(422, $this->save(['flarum-ai.default_connection' => 'missing']));
        $this->assertSame(204, $this->save(['flarum-ai.default_connection' => 'host']));
        $this->assertSame(204, $this->save(['flarum-ai.default_connection' => 'local']));
        $this->assertSame(204, $this->save(['flarum-ai.default_connection' => '']));

        // Checked against the connections saved alongside it.
        $this->assertSame(204, $this->save([
            'flarum-ai.connections' => json_encode(['new' => ['driver' => 'ollama']]),
            'flarum-ai.default_connection' => 'new',
        ]));
        $this->assertSame(422, $this->save([
            'flarum-ai.connections' => json_encode(['other' => ['driver' => 'ollama']]),
            'flarum-ai.default_connection' => 'new',
        ]));
    }

    #[Test]
    public function only_admins_can_save_connections(): void
    {
        $this->assertSame(403, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'key' => 'k']])], 2));
        $this->assertSame([], $this->stored());
    }
}
