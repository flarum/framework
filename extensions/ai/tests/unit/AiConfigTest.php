<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\unit;

use Flarum\Ai\AiConfig;
use Flarum\Foundation\Config;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\UninstalledSettingsRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AiConfigTest extends TestCase
{
    protected function aiConfig(array $settings = [], array $configPhp = []): AiConfig
    {
        return new AiConfig(
            new OverrideSettingsRepository(new UninstalledSettingsRepository(), $settings),
            new Config(['url' => 'https://forum.test', ...($configPhp ? ['ai' => $configPhp] : [])])
        );
    }

    #[Test]
    public function connections_come_from_the_admins_settings(): void
    {
        $config = $this->aiConfig([
            'flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama', 'url' => 'http://ollama:11434']]),
        ]);

        $this->assertSame(['local' => ['driver' => 'ollama', 'url' => 'http://ollama:11434']], $config->connections());
    }

    #[Test]
    public function connections_without_a_driver_are_ignored(): void
    {
        $config = $this->aiConfig([
            'flarum-ai.connections' => json_encode([
                'no-driver' => ['key' => 'secret'],
                'empty-driver' => ['driver' => ''],
                'not-a-connection' => 'ollama',
                AiConfig::UNCONFIGURED => ['driver' => 'openai'],
                'ok' => ['driver' => 'openai'],
            ]),
        ]);

        $this->assertSame(['ok'], array_keys($config->connections()));
    }

    #[Test]
    public function settings_that_are_not_json_mean_no_connections(): void
    {
        $this->assertSame([], $this->aiConfig(['flarum-ai.connections' => 'not json'])->connections());
    }

    #[Test]
    public function config_php_connections_replace_the_admins_connection_of_the_same_name(): void
    {
        $config = $this->aiConfig(
            ['flarum-ai.connections' => json_encode([
                'openai' => ['driver' => 'openai', 'key' => 'admin-key'],
                'local' => ['driver' => 'ollama'],
            ])],
            ['connections' => ['openai' => ['driver' => 'openai', 'key' => 'host-key']]]
        );

        $this->assertSame([
            'openai' => ['driver' => 'openai', 'key' => 'host-key'],
            'local' => ['driver' => 'ollama'],
        ], $config->connections());
        $this->assertSame(['openai'], array_keys($config->lockedConnections()));
    }

    #[Test]
    public function config_php_chooses_the_default_over_the_admin(): void
    {
        $config = $this->aiConfig(
            [
                'flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama'], 'openai' => ['driver' => 'openai']]),
                'flarum-ai.default_connection' => 'local',
            ],
            ['default_connection' => 'openai']
        );

        $this->assertSame('openai', $config->defaultConnection());
    }

    #[Test]
    public function a_default_that_names_no_connection_is_no_default(): void
    {
        $config = $this->aiConfig([
            'flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama']]),
            'flarum-ai.default_connection' => 'removed',
        ]);

        $this->assertNull($config->defaultConnection());
        $this->assertSame(AiConfig::UNCONFIGURED, $config->toArray()['default']);
    }

    #[Test]
    public function laravel_ai_gets_the_connections_as_providers(): void
    {
        $config = $this->aiConfig([
            'flarum-ai.connections' => json_encode(['local' => ['driver' => 'ollama']]),
            'flarum-ai.default_connection' => 'local',
        ])->toArray();

        $this->assertSame('local', $config['default']);
        $this->assertSame([
            'local' => ['driver' => 'ollama'],
            AiConfig::UNCONFIGURED => ['driver' => AiConfig::UNCONFIGURED],
        ], $config['providers']);
        $this->assertFalse($config['caching']['embeddings']['cache']);
    }
}
