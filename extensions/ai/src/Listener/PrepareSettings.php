<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\AdminConnections;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Ai\Usage\Limits;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Validates the AI settings in a save. Runs before any setting in the save is
 * written, so a rejected value leaves every setting as it was.
 */
class PrepareSettings
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiConfig $config,
        protected FeatureRegistry $features,
        protected FeatureSettings $featureSettings,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(Saving $event): void
    {
        if (array_key_exists(AdminConnections::SETTING, $event->settings)) {
            $event->settings[AdminConnections::SETTING] = $this->connections->prepare(
                $event->settings[AdminConnections::SETTING],
                $this->settings->get(AdminConnections::SETTING)
            );

            $adminNames = array_keys(json_decode($event->settings[AdminConnections::SETTING], true));
        } else {
            $adminNames = array_keys($this->config->adminConnections());
        }

        $names = [...$adminNames, ...array_keys($this->config->lockedConnections())];

        if (array_key_exists(AdminConnections::DEFAULT_SETTING, $event->settings)) {
            $this->connections->assertDefaultExists($event->settings[AdminConnections::DEFAULT_SETTING], $names);
        }

        if (array_key_exists(FeatureSettings::SETTING, $event->settings)) {
            $event->settings[FeatureSettings::SETTING] = $this->prepareFeatures($event->settings[FeatureSettings::SETTING], $names);
        }

        if (array_key_exists(Limits::MEMBER_DAILY_SETTING, $event->settings)) {
            $event->settings[Limits::MEMBER_DAILY_SETTING] = (string) $this->connections->tokens(
                $event->settings[Limits::MEMBER_DAILY_SETTING],
                '',
                Limits::MEMBER_DAILY_SETTING
            );
        }
    }

    /**
     * @param string[] $connectionNames
     */
    protected function prepareFeatures(mixed $submitted, array $connectionNames): string
    {
        $features = is_string($submitted) ? json_decode($submitted, true) : null;

        if (! is_array($features) || ($features !== [] && array_is_list($features))) {
            $this->connections->fail('features_invalid_message', [], FeatureSettings::SETTING);
        }

        // Kept as they were: features of extensions that are turned off, which
        // the admin page doesn't list.
        $prepared = array_diff_key($this->featureSettings->all(), $this->features->all());

        foreach ($this->features->all() as $key => $feature) {
            $settings = $features[$key] ?? null;

            if ($settings === null) {
                continue;
            }

            if (! is_array($settings) || ! is_bool($settings['enabled'] ?? false)) {
                $this->connections->fail('features_invalid_message', [], FeatureSettings::SETTING);
            }

            $connection = $settings['connection'] ?? null;

            if ($connection !== null && $connection !== '' && ! in_array($connection, $connectionNames, true)) {
                $this->connections->fail('feature_connection_invalid_message', ['name' => $key], FeatureSettings::SETTING);
            }

            $model = is_string($settings['model'] ?? null) ? trim($settings['model']) : null;

            if ($model !== null && mb_strlen($model) > 100) {
                $this->connections->fail('model_invalid_message', ['name' => $key], FeatureSettings::SETTING);
            }

            $prepared[$key] = array_filter([
                'enabled' => $settings['enabled'] ?? false,
                'connection' => $connection,
                'model' => $model,
                'daily_tokens' => $this->connections->tokens($settings['daily_tokens'] ?? null, $key, FeatureSettings::SETTING),
            ], fn ($value) => $value !== null && $value !== '');
        }

        return json_encode((object) $prepared, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
