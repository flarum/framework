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
/**
 * The connections laravel/ai is configured with: the ones the admin set up on
 * the admin page, and the ones in config.php, which take precedence.
 *
 * @internal
 */
class AiConfig
{
    /**
     * The default when no connection is configured. Its driver throws a
     * NotConfiguredException, so callers learn what's missing instead of
     * getting laravel/ai's error about a null provider.
     */
    public const UNCONFIGURED = 'flarum-ai-unconfigured';

    /**
     * The longest connection name the usage ledger can hold.
     */
    public const NAME_MAX_LENGTH = 100;

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

    /**
     * The default connection chosen in config.php, which the admin can't change.
     */
    public function lockedDefaultConnection(): ?string
    {
        $name = $this->config['ai.default_connection'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function defaultConnection(): ?string
    {
        $name = $this->lockedDefaultConnection() ?? $this->settings->get('flarum-ai.default_connection');

        return is_string($name) && isset($this->connections()[$name]) ? $name : null;
    }

    /**
     * Connections the admin set up on the admin page, without their keys,
     * which are kept in their own table ({@see ConnectionKeys}).
     *
     * @return array<string, array<string, mixed>>
     */
    public function adminConnections(): array
    {
        $connections = $this->valid(json_decode((string) $this->settings->get('flarum-ai.connections'), true));

        // Keys only ever come from their table, even if one found its way into the setting.
        return array_map(function (array $connection) {
            unset($connection['key']);

            return $connection;
        }, $connections);
    }

    /**
     * Whether a connection is one set up on the admin page, rather than in
     * config.php, so its key is in {@see ConnectionKeys}.
     */
    public function isAdminConnection(string $name): bool
    {
        return isset($this->adminConnections()[$name]) && ! isset($this->lockedConnections()[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default' => $this->defaultConnection() ?? self::UNCONFIGURED,
            'providers' => [
                ...array_map($this->forLaravelAi(...), $this->connections()),
                self::UNCONFIGURED => ['driver' => self::UNCONFIGURED],
            ],
            'caching' => [
                // Off: it goes through Cache::store(), which core's cache factory can't serve yet.
                'embeddings' => ['cache' => false],
            ],
        ];
    }

    /**
     * A connection as laravel/ai expects a provider. Its own settings stay
     * out: laravel/ai passes a provider's extra keys on to the gateway.
     *
     * @param array<string, mixed> $connection
     * @return array<string, mixed>
     */
    protected function forLaravelAi(array $connection): array
    {
        $model = $connection['model'] ?? null;

        unset($connection['model'], $connection['monthly_tokens']);

        if (is_string($model) && $model !== '') {
            $connection['models']['text']['default'] = $model;
        }

        return $connection;
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
                && strlen($name) <= self::NAME_MAX_LENGTH
                && is_array($connection)
                && is_string($connection['driver'] ?? null)
                && $connection['driver'] !== '',
            ARRAY_FILTER_USE_BOTH
        );
    }
}
