<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\Settings\Event\Saving;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Connections set up on the admin page. API keys are write-only, and kept in
 * their own table, never among the settings the admin page is sent. A
 * connection saved without a key keeps its stored one only while its
 * provider and address stay the same.
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

    /**
     * @param array<string, array<string, mixed>> $connections
     * @param array<string, string> $keys
     */
    protected function store(array $connections, array $keys = [], ?string $default = null): void
    {
        $this->setting('flarum-ai.connections', json_encode($connections));

        if ($default !== null) {
            $this->setting('flarum-ai.default_connection', $default);
        }

        if ($keys) {
            $this->prepareDatabase(['ai_connection_keys' => array_map(
                fn ($name, $key) => ['connection' => $name, 'key' => $key, 'updated_at' => date('Y-m-d H:i:s')],
                array_keys($keys),
                $keys
            )]);
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

    protected function storedKeys(): array
    {
        return $this->database()->table('ai_connection_keys')->orderBy('connection')->pluck('key', 'connection')->all();
    }

    protected function storedDefault(): ?string
    {
        return $this->database()->table('settings')->where('key', 'flarum-ai.default_connection')->value('value');
    }

    #[Test]
    public function the_admin_page_is_never_sent_a_key(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']], ['claude' => self::KEY]);

        $page = $this->adminPage();
        $payload = $this->payload($page);

        $this->assertStringNotContainsString(self::KEY, $page);
        $this->assertSame(['claude'], $payload['flarum-ai']['connectionsWithKeys']);
        $this->assertSame(
            ['claude' => ['driver' => 'anthropic', 'url' => 'https://api.anthropic.test/v1']],
            json_decode($payload['settings']['flarum-ai.connections'], true)
        );
    }

    #[Test]
    public function a_saved_key_goes_to_its_own_table_never_the_settings(): void
    {
        // The admin page is sent every setting, whether this extension is
        // enabled or not, so a key must never be one.
        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'key' => self::KEY]])]));

        $this->assertStringNotContainsString(self::KEY, json_encode($this->database()->table('settings')->pluck('value')->all()));
        $this->assertSame(['claude' => self::KEY], $this->storedKeys());
    }

    #[Test]
    public function config_php_connections_are_shown_without_their_keys_or_url_credentials(): void
    {
        $this->config('ai', [
            'default_connection' => 'host',
            'connections' => ['host' => ['driver' => 'azure', 'key' => self::KEY, 'url' => 'https://user:pass@example.test/openai?token=abc#x']],
        ]);

        $page = $this->adminPage();
        $payload = $this->payload($page)['flarum-ai'];

        $this->assertStringNotContainsString(self::KEY, $page);
        $this->assertStringNotContainsString('pass@', $page);
        $this->assertStringNotContainsString('token=abc', $page);
        $this->assertSame(['host' => ['driver' => 'azure', 'url' => 'https://example.test/openai', 'hasKey' => true]], $payload['lockedConnections']);
        $this->assertSame('host', $payload['lockedDefault']);
        $this->assertContains('anthropic', $payload['drivers']);
        $this->assertSame('^[a-z][a-z0-9_-]{0,49}$', $payload['rules']['namePattern']);
    }

    #[Test]
    public function a_new_connection_is_saved_with_its_key(): void
    {
        $status = $this->save([
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'model' => ' claude-x ', 'key' => ' '.self::KEY.' ']]),
            'flarum-ai.default_connection' => 'claude',
        ]);

        $this->assertSame(204, $status);
        $this->assertSame(['claude' => ['driver' => 'anthropic', 'model' => 'claude-x']], $this->stored());
        $this->assertSame(['claude' => self::KEY], $this->storedKeys());
        $this->assertSame('claude', $this->storedDefault());
    }

    #[Test]
    public function a_connection_saved_without_a_key_keeps_it_while_provider_and_url_are_unchanged(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic', 'url' => 'https://proxy.test/v1']], ['claude' => self::KEY]);

        $this->assertSame(204, $this->save([
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://PROXY.test/v1/', 'model' => 'claude-y', 'hasKey' => true]]),
        ]));

        $this->assertSame(['claude' => ['driver' => 'anthropic', 'url' => 'https://PROXY.test/v1/', 'model' => 'claude-y']], $this->stored());
        $this->assertSame(['claude' => self::KEY], $this->storedKeys());
    }

    #[Test]
    public function a_key_is_not_sent_to_a_new_address_without_being_typed_again(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic']], ['claude' => self::KEY]);

        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://attacker.test/v1']])]));
        $this->assertSame(['claude' => self::KEY], $this->storedKeys());

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'url' => 'https://proxy.test/v1', 'key' => 'sk-new']])]));
        $this->assertSame(['claude' => 'sk-new'], $this->storedKeys());
    }

    #[Test]
    public function a_key_is_dropped_when_the_address_changes_on_a_provider_that_needs_none(): void
    {
        $this->store(['local' => ['driver' => 'ollama', 'url' => 'http://ollama.test']], ['local' => self::KEY]);

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama', 'url' => 'http://elsewhere.test']])]));

        $this->assertSame([], $this->storedKeys());
    }

    #[Test]
    public function a_key_is_not_kept_for_a_different_provider(): void
    {
        $this->store(['main' => ['driver' => 'anthropic']], ['main' => self::KEY]);

        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['main' => ['driver' => 'openai']])]));
        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['main' => ['driver' => 'openai', 'key' => 'sk-openai']])]));
        $this->assertSame(['main' => 'sk-openai'], $this->storedKeys());
    }

    #[Test]
    public function an_empty_key_removes_the_stored_one(): void
    {
        $this->store(['local' => ['driver' => 'ollama']], ['local' => self::KEY]);

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama', 'key' => '']])]));
        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());
        $this->assertSame([], $this->storedKeys());
    }

    #[Test]
    public function removing_a_connection_removes_its_key(): void
    {
        $this->store(['claude' => ['driver' => 'anthropic'], 'local' => ['driver' => 'ollama']], ['claude' => self::KEY]);

        $this->assertSame(204, $this->save(['flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama']])]));
        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());
        $this->assertSame([], $this->storedKeys());

        // Adding it back under the same name doesn't bring the old key back.
        $this->assertSame(422, $this->save(['flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic']])]));
    }

    #[Test]
    public function a_save_rejected_after_the_keys_were_checked_writes_none(): void
    {
        // Another extension rejects the same save after this one has checked it.
        $this->extend((new Extend\Event())->listen(Saving::class, function (Saving $event) {
            if (isset($event->settings['forum_title'])) {
                throw new ValidationException(['forum_title' => 'No.']);
            }
        }));

        $this->assertSame(422, $this->save([
            'flarum-ai.connections' => json_encode(['claude' => ['driver' => 'anthropic', 'key' => self::KEY]]),
            'forum_title' => 'Changed',
        ]));

        $this->assertSame([], $this->storedKeys());
        $this->assertSame([], $this->stored());
    }

    public static function invalidConnections(): array
    {
        return [
            'not an object' => ['["claude"]'],
            'not json' => ['{claude'],
            'name with a space' => [json_encode(['my claude' => ['driver' => 'anthropic', 'key' => 'k']])],
            'name that is a number' => [json_encode(['123' => ['driver' => 'anthropic', 'key' => 'k']])],
            'name starting with a digit' => [json_encode(['1claude' => ['driver' => 'anthropic', 'key' => 'k']])],
            'name too long' => [json_encode([str_repeat('a', 51) => ['driver' => 'anthropic', 'key' => 'k']])],
            'reserved name' => [json_encode(['flarum-ai-unconfigured' => ['driver' => 'anthropic', 'key' => 'k']])],
            'provider the page does not offer' => [json_encode(['claude' => ['driver' => 'bedrock', 'key' => 'k']])],
            'no provider' => [json_encode(['claude' => ['key' => 'k']])],
            'not an object for a connection' => [json_encode(['claude' => 'anthropic'])],
            'url that is not http' => [json_encode(['claude' => ['driver' => 'anthropic', 'key' => 'k', 'url' => 'file:///etc/passwd']])],
            'model too long' => [json_encode(['claude' => ['driver' => 'anthropic', 'key' => 'k', 'model' => str_repeat('m', 101)]])],
            'missing key' => [json_encode(['claude' => ['driver' => 'anthropic']])],
            'compatible server without a url' => [json_encode(['own' => ['driver' => 'openai-compatible']])],
        ];
    }

    #[Test]
    #[DataProvider('invalidConnections')]
    public function invalid_connections_are_rejected_and_nothing_is_saved(string $connections): void
    {
        $this->store(['local' => ['driver' => 'ollama']], [], 'local');

        $this->assertSame(422, $this->save([
            'flarum-ai.connections' => $connections,
            'flarum-ai.default_connection' => '',
            'forum_title' => 'Changed',
        ]));

        $this->assertSame(['local' => ['driver' => 'ollama']], $this->stored());
        $this->assertSame([], $this->storedKeys());
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
        $this->assertSame([], $this->storedKeys());
    }
}
