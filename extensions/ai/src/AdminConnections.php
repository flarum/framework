<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;

/**
 * The connections an admin sets up on the admin page, stored as JSON in the
 * `flarum-ai.connections` setting.
 *
 * API keys are write-only: the admin page is sent each connection with
 * `hasKey` in place of its key, and a connection saved without a `key` keeps
 * the one already stored.
 */
class AdminConnections
{
    public const SETTING = 'flarum-ai.connections';

    public const DEFAULT_SETTING = 'flarum-ai.default_connection';

    public function __construct(
        protected AiConfig $config,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * The connections as the admin page may see them.
     *
     * @param array<string, array<string, mixed>> $connections
     * @return array<string, array{driver: string, url?: string, hasKey: bool}>
     */
    public function withoutKeys(array $connections): array
    {
        return array_map(fn (array $connection) => array_filter([
            'driver' => $connection['driver'],
            'url' => is_string($connection['url'] ?? null) && $connection['url'] !== '' ? $connection['url'] : null,
        ]) + ['hasKey' => is_string($connection['key'] ?? null) && $connection['key'] !== ''], $connections);
    }

    /**
     * Validates the connections submitted from the admin page and fills in the
     * keys left out of them, ready to store.
     *
     * @throws ValidationException
     */
    public function prepare(mixed $submitted, ?string $stored): string
    {
        $connections = is_string($submitted) ? json_decode($submitted, true) : null;

        if (! is_array($connections) || ($connections !== [] && array_is_list($connections))) {
            $this->fail('invalid_message');
        }

        $previous = json_decode((string) $stored, true);
        $previous = is_array($previous) ? $previous : [];
        $locked = $this->config->lockedConnections();

        $prepared = [];

        foreach ($connections as $name => $connection) {
            $name = (string) $name;

            if (! preg_match('/^[a-z0-9][a-z0-9_-]{0,49}$/i', $name) || $name === AiConfig::UNCONFIGURED) {
                $this->fail('name_invalid_message', ['name' => $name]);
            }

            if (isset($locked[$name])) {
                $this->fail('name_locked_message', ['name' => $name]);
            }

            $driver = is_array($connection) ? ($connection['driver'] ?? null) : null;

            if (! in_array($driver, Drivers::ADMIN, true)) {
                $this->fail('driver_invalid_message', ['name' => $name]);
            }

            $url = $connection['url'] ?? null;
            $url = is_string($url) ? trim($url) : null;

            if ($url !== null && $url !== '' && ! $this->isUrl($url)) {
                $this->fail('url_invalid_message', ['name' => $name]);
            }

            if (($url === null || $url === '') && in_array($driver, Drivers::URL_REQUIRED, true)) {
                $this->fail('url_required_message', ['name' => $name]);
            }

            // Left out: keep the stored key, as long as it was for the same
            // provider. An empty string removes it.
            if (array_key_exists('key', $connection)) {
                $key = is_string($connection['key']) ? trim($connection['key']) : '';
            } else {
                $key = ($previous[$name]['driver'] ?? null) === $driver ? (string) ($previous[$name]['key'] ?? '') : '';
            }

            if ($key === '' && ! in_array($driver, Drivers::KEY_OPTIONAL, true)) {
                $this->fail('key_required_message', ['name' => $name]);
            }

            $prepared[$name] = array_filter(['driver' => $driver, 'url' => $url, 'key' => $key], fn ($value) => $value !== null && $value !== '');
        }

        return json_encode((object) $prepared, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Checks the default the admin chose names a connection that exists.
     *
     * @param string[] $names
     *
     * @throws ValidationException
     */
    public function assertDefaultExists(mixed $default, array $names): void
    {
        if ($default === null || $default === '') {
            return;
        }

        if (! is_string($default) || ! in_array($default, $names, true)) {
            $this->fail('default_invalid_message', [], self::DEFAULT_SETTING);
        }
    }

    protected function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * @param array<string, string> $parameters
     *
     * @throws ValidationException
     */
    protected function fail(string $message, array $parameters = [], string $setting = self::SETTING): never
    {
        throw new ValidationException([
            $setting => $this->translator->trans("flarum-ai.admin.connections.$message", $parameters),
        ]);
    }
}
