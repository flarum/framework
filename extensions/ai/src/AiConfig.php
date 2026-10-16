<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Builds the Laravel AI SDK's `ai` config from the admin's settings. Anything
 * under `ai` in config.php wins, so a host can fix connections the admin
 * can't change.
 *
 * A connection is a laravel/ai provider config, keyed by a name of the admin's
 * choosing: `['driver' => 'ollama', 'url' => '...', 'key' => '...']`.
 */
class AiConfig
{
    /**
     * The default when no connection is configured. Its driver throws a
     * NotConfiguredException, so callers learn what's missing instead of
     * getting laravel/ai's error about a null provider.
     */
    public const UNCONFIGURED = 'flarum-ai-unconfigured';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Config $config
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function connections(): array
    {
        return array_merge($this->adminConnections(), $this->lockedConnections());
    }

    /**
     * Connections defined in config.php, which the admin can't change.
     *
     * @return array<string, array<string, mixed>>
     */
    public function lockedConnections(): array
    {
        return $this->valid($this->config['ai.connections']);
    }

    public function defaultConnection(): ?string
    {
        $name = $this->config['ai.default_connection'] ?? $this->settings->get('flarum-ai.default_connection');

        return is_string($name) && isset($this->connections()[$name]) ? $name : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default' => $this->defaultConnection() ?? self::UNCONFIGURED,
            'providers' => [
                ...$this->connections(),
                self::UNCONFIGURED => ['driver' => self::UNCONFIGURED],
            ],
            'caching' => [
                // Off: it goes through Cache::store(), which core's cache factory can't serve yet.
                'embeddings' => ['cache' => false],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function adminConnections(): array
    {
        return $this->valid(json_decode((string) $this->settings->get('flarum-ai.connections'), true));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function valid(mixed $connections): array
    {
        if (! is_array($connections)) {
            return [];
        }

        return array_filter(
            $connections,
            fn ($connection, $name) => is_string($name)
                && $name !== self::UNCONFIGURED
                && is_array($connection)
                && is_string($connection['driver'] ?? null)
                && $connection['driver'] !== '',
            ARRAY_FILTER_USE_BOTH
        );
    }
}
