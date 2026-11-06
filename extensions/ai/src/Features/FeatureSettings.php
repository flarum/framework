<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * How the admin set each feature up, stored as JSON in `flarum-ai.features`.
 * A feature the admin hasn't turned on is off: it costs money.
 *
 * @internal
 */
class FeatureSettings
{
    public const SETTING = 'flarum-ai.features';

    /**
     * The widest range providers accept. Some accept less (Anthropic stops at
     * 1), and reject a call with a temperature outside theirs.
     */
    public const TEMPERATURE_MIN = 0;

    public const TEMPERATURE_MAX = 2;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return array{enabled: bool, connection: string|null, model: string|null, daily_tokens: int|null, temperature: float|null, max_tokens: int|null}
     */
    public function get(string $key): array
    {
        $feature = $this->all()[$key] ?? [];

        return [
            'enabled' => ($feature['enabled'] ?? false) === true,
            'connection' => is_string($feature['connection'] ?? null) && $feature['connection'] !== '' ? $feature['connection'] : null,
            'model' => is_string($feature['model'] ?? null) && $feature['model'] !== '' ? $feature['model'] : null,
            'daily_tokens' => is_int($feature['daily_tokens'] ?? null) && $feature['daily_tokens'] > 0 ? $feature['daily_tokens'] : null,
            'temperature' => (is_int($feature['temperature'] ?? null) || is_float($feature['temperature'] ?? null)) && $feature['temperature'] >= self::TEMPERATURE_MIN && $feature['temperature'] <= self::TEMPERATURE_MAX ? (float) $feature['temperature'] : null,
            'max_tokens' => is_int($feature['max_tokens'] ?? null) && $feature['max_tokens'] > 0 ? $feature['max_tokens'] : null,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $features = json_decode((string) $this->settings->get(self::SETTING), true);

        return is_array($features) ? array_filter($features, 'is_array') : [];
    }
}
