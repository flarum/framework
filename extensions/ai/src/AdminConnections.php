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
 * `flarum-ai.connections` setting, with their API keys in {@see ConnectionKeys}.
 *
 * Keys are write-only. The admin page is told whether each connection has
 * one, never what it is. A connection saved without a key keeps its stored
 * one only while its provider and API URL stay the same: a key isn't sent
 * somewhere new without being typed in again.
 *
 * @internal
 */
class AdminConnections
{
    public const SETTING = 'flarum-ai.connections';

    public const DEFAULT_SETTING = 'flarum-ai.default_connection';

    /**
     * Starts with a letter, so a name is never decoded from JSON as a number.
     */
    public const NAME_PATTERN = '^[a-z][a-z0-9_-]{0,49}$';

    public const MODEL_MAX_LENGTH = 100;

    /**
     * An AWS region, such as eu-central-1 or us-gov-west-1.
     */
    public const REGION_PATTERN = '^[a-z]{2}(-gov|-iso[a-z]?)?-[a-z]+-\d{1,2}$';

    /**
     * A Bedrock guardrail's ID or ARN.
     */
    public const GUARDRAIL_PATTERN = '^[A-Za-z0-9:\/._-]{1,2048}$';

    /**
     * A guardrail's working draft, or one of its numbered versions.
     */
    public const GUARDRAIL_VERSION_PATTERN = '^(DRAFT|[1-9][0-9]{0,7})$';

    public function __construct(
        protected AiConfig $config,
        protected ConnectionKeys $keys,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * Connections as the admin page may see them: whether each has a key,
     * never the key, and an API URL without anything that could be a credential.
     *
     * @param array<string, array<string, mixed>> $connections
     * @param string[]|null $withKeys The connections with a key in their table; null to look for a `key` in each.
     * @return array<string, array{driver: string, url?: string, region?: string, model?: string, monthly_tokens?: int, guardrail_id?: string, guardrail_version?: string, hasKey: bool}>
     */
    public function forAdmin(array $connections, ?array $withKeys = null): array
    {
        $result = [];

        foreach ($connections as $name => $connection) {
            $result[$name] = array_filter([
                'driver' => $connection['driver'],
                'url' => is_string($connection['url'] ?? null) && $connection['url'] !== '' ? $this->displayUrl($connection['url']) : null,
                'region' => is_string($connection['region'] ?? null) && $connection['region'] !== '' ? $connection['region'] : null,
                'model' => is_string($connection['model'] ?? null) && $connection['model'] !== '' ? $connection['model'] : null,
                'monthly_tokens' => is_int($connection['monthly_tokens'] ?? null) && $connection['monthly_tokens'] > 0 ? $connection['monthly_tokens'] : null,
                'guardrail_id' => is_string($connection['guardrail_id'] ?? null) && $connection['guardrail_id'] !== '' ? $connection['guardrail_id'] : null,
                // config.php may give the version as a number.
                'guardrail_version' => (is_string($connection['guardrail_version'] ?? null) && $connection['guardrail_version'] !== '') || is_int($connection['guardrail_version'] ?? null) ? (string) $connection['guardrail_version'] : null,
            ]) + ['hasKey' => $withKeys === null
                ? is_string($connection['key'] ?? null) && $connection['key'] !== ''
                : in_array($name, $withKeys, true)];
        }

        return $result;
    }

    /**
     * Validates the connections submitted from the admin page and works out
     * the changes they make to the stored keys.
     *
     * @return array{0: string, 1: array<string, string|null>} The JSON to store, without keys, and each
     *                                                         connection's new key, or null to remove it.
     *
     * @throws ValidationException
     */
    public function prepare(mixed $submitted, ?string $stored): array
    {
        $connections = is_string($submitted) ? json_decode($submitted, true) : null;

        if (! is_array($connections) || ($connections !== [] && array_is_list($connections))) {
            $this->fail('invalid_message');
        }

        $previous = json_decode((string) $stored, true);
        $previous = is_array($previous) ? $previous : [];
        $storedKeys = $this->keys->names();
        $locked = $this->config->lockedConnections();

        $prepared = [];
        $keyChanges = [];

        foreach ($connections as $name => $connection) {
            $name = (string) $name;

            if (! preg_match('/'.self::NAME_PATTERN.'/i', $name) || $name === AiConfig::UNCONFIGURED) {
                $this->fail('name_invalid_message', ['name' => $name]);
            }

            if (isset($locked[$name])) {
                $this->fail('name_locked_message', ['name' => $name]);
            }

            $driver = is_array($connection) ? ($connection['driver'] ?? null) : null;

            if (! is_string($driver) || ! Drivers::allowed($driver, is_string($previous[$name]['driver'] ?? null) ? $previous[$name]['driver'] : null)) {
                $this->fail('driver_invalid_message', ['name' => $name]);
            }

            $byRegion = in_array($driver, Drivers::REGION_REQUIRED, true);

            // A provider reached by region has no address to keep.
            $url = ! $byRegion && is_string($connection['url'] ?? null) ? trim($connection['url']) : '';
            $region = $byRegion && is_string($connection['region'] ?? null) ? trim($connection['region']) : '';

            if ($byRegion && ! preg_match('/'.self::REGION_PATTERN.'/', $region)) {
                $this->fail($region === '' ? 'region_required_message' : 'region_invalid_message', ['name' => $name]);
            }

            [$guardrailId, $guardrailVersion] = $driver === 'bedrock' ? $this->guardrail($connection, $name) : [null, null];

            if ($url !== '' && ! $this->isUrl($url)) {
                $this->fail('url_invalid_message', ['name' => $name]);
            }

            if ($url === '' && in_array($driver, Drivers::URL_REQUIRED, true)) {
                $this->fail('url_required_message', ['name' => $name]);
            }

            $model = is_string($connection['model'] ?? null) ? trim($connection['model']) : '';

            if (mb_strlen($model) > self::MODEL_MAX_LENGTH) {
                $this->fail('model_invalid_message', ['name' => $name]);
            }

            $monthly = $this->tokens($connection['monthly_tokens'] ?? null, $name);

            if (array_key_exists('key', $connection)) {
                $key = is_string($connection['key']) ? trim($connection['key']) : '';
                // laravel/ai drops a key PHP thinks is empty, so it's no key.
                $key = $key === '0' ? '' : $key;
                $keyChanges[$name] = $key === '' ? null : $key;
                $hasKey = $key !== '';
            } elseif ($this->sameTarget($previous[$name] ?? null, $driver, $url, $region)) {
                $hasKey = in_array($name, $storedKeys, true);
            } else {
                $keyChanges[$name] = null;
                $hasKey = false;
            }

            if (! $hasKey && ! in_array($driver, Drivers::keyOptional($this->config->bedrockServerCredentials()), true)) {
                $this->fail('key_required_message', ['name' => $name]);
            }

            $prepared[$name] = array_filter(
                [
                    'driver' => $driver,
                    'url' => $url,
                    'region' => $region,
                    'model' => $model,
                    'monthly_tokens' => $monthly,
                    'guardrail_id' => $guardrailId,
                    'guardrail_version' => $guardrailVersion,
                ],
                fn ($value) => $value !== null && $value !== ''
            );
        }

        // A removed connection takes its key with it.
        foreach ($storedKeys as $name) {
            if (! isset($prepared[$name])) {
                $keyChanges[$name] = null;
            }
        }

        return [json_encode((object) $prepared, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $keyChanges];
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

    /**
     * A token limit: a whole number above zero, or nothing for no limit.
     *
     * @throws ValidationException
     */
    public function tokens(mixed $value, string $name, string $setting = self::SETTING, string $message = 'tokens_invalid_message'): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $value = (int) trim($value);
        }

        if (! is_int($value) || $value < 1) {
            $this->fail($message, ['name' => $name], $setting);
        }

        return $value;
    }

    /**
     * @param array<string, string> $parameters
     *
     * @throws ValidationException
     */
    public function fail(string $message, array $parameters = [], string $setting = self::SETTING): never
    {
        throw new ValidationException([
            $setting => $this->translator->trans("flarum-ai.admin.connections.$message", $parameters),
        ]);
    }

    /**
     * Whether a stored key still belongs: same provider, same address, same region.
     */
    protected function sameTarget(mixed $previous, string $driver, string $url, string $region = ''): bool
    {
        return is_array($previous)
            && ($previous['driver'] ?? null) === $driver
            && $this->normaliseUrl(is_string($previous['url'] ?? null) ? $previous['url'] : '') === $this->normaliseUrl($url)
            && (is_string($previous['region'] ?? null) ? $previous['region'] : '') === $region;
    }

    /**
     * A Bedrock connection's guardrail: its ID or ARN, and the version to use,
     * the working draft unless one is given.
     *
     * @param array<string, mixed> $connection
     * @return array{0: string|null, 1: string|null}
     *
     * @throws ValidationException
     */
    protected function guardrail(array $connection, string $name): array
    {
        $id = is_string($connection['guardrail_id'] ?? null) ? trim($connection['guardrail_id']) : '';

        if ($id === '') {
            return [null, null];
        }

        $version = is_string($connection['guardrail_version'] ?? null) || is_int($connection['guardrail_version'] ?? null)
            ? trim((string) $connection['guardrail_version'])
            : '';
        $version = $version === '' ? 'DRAFT' : $version;

        if (! preg_match('/'.self::GUARDRAIL_PATTERN.'/', $id) || ! preg_match('/'.self::GUARDRAIL_VERSION_PATTERN.'/', $version)) {
            $this->fail('guardrail_invalid_message', ['name' => $name]);
        }

        return [$id, $version];
    }

    protected function normaliseUrl(string $url): string
    {
        return rtrim(strtolower(trim($url)), '/');
    }

    /**
     * An API URL as the admin page shows it: no username, password, query or
     * fragment, any of which can carry a credential.
     */
    protected function displayUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '');
    }

    protected function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
