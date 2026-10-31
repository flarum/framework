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
 */
class FeatureSettings
{
    public const SETTING = 'flarum-ai.features';

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return array{enabled: bool, connection: string|null, model: string|null, daily_tokens: int|null}
     */
    public function get(string $key): array
    {
        $feature = $this->all()[$key] ?? [];

        return [
            'enabled' => ($feature['enabled'] ?? false) === true,
            'connection' => is_string($feature['connection'] ?? null) && $feature['connection'] !== '' ? $feature['connection'] : null,
            'model' => is_string($feature['model'] ?? null) && $feature['model'] !== '' ? $feature['model'] : null,
            'daily_tokens' => is_int($feature['daily_tokens'] ?? null) && $feature['daily_tokens'] > 0 ? $feature['daily_tokens'] : null,
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
