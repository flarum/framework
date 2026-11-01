<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Testing;

use Flarum\Ai\AdminConnections;
use Flarum\Ai\Features\FeatureSettings;

/**
 * For an extension's integration tests: a connection to make calls through
 * and a feature turned on, as an admin would set them up. Use it in a
 * `Flarum\Testing\integration\TestCase`, before the app boots.
 *
 * Calls still reach the provider's API, so fake them: laravel/ai's own fakes
 * (`YourAgent::fake(['A reply'])`), or `Illuminate\Support\Facades\Http::fake()`
 * to go through the real gateway. With the gateway faked, the usage is recorded
 * and the limits apply as in production.
 *
 *     $this->aiConnection();
 *     $this->enableAiFeature('acme-tldr.summaries', ['daily_tokens' => 1000]);
 *     $this->grantAiFeature('acme-tldr.summaries', Group::MEMBER_ID);
 */
trait InteractsWithAi
{
    /**
     * Adds a connection and makes it the default. Calling it again with the
     * same name replaces that connection, key included.
     *
     * @param array{driver?: string, url?: string, model?: string, monthly_tokens?: int} $connection
     */
    protected function aiConnection(string $name = 'test', array $connection = [], ?string $key = 'test-key'): void
    {
        $connections = json_decode((string) ($this->settings[AdminConnections::SETTING] ?? '{}'), true) ?: [];
        $connections[$name] = $connection + ['driver' => 'anthropic', 'url' => 'https://ai.test/v1'];

        $this->setting(AdminConnections::SETTING, json_encode($connections));
        $this->setting(AdminConnections::DEFAULT_SETTING, $name);

        // The table has no model, so the test case would insert a second row for
        // the connection, not update the first, and break its primary key.
        $this->databaseContent['ai_connection_keys'] = array_values(array_filter(
            $this->databaseContent['ai_connection_keys'] ?? [],
            fn (array $row) => $row['connection'] !== $name
        ));

        if ($key !== null) {
            $this->prepareDatabase(['ai_connection_keys' => [['connection' => $name, 'key' => $key, 'updated_at' => date('Y-m-d H:i:s')]]]);
        }
    }

    /**
     * Turns a feature on, with any of its other settings.
     *
     * @param array{connection?: string, model?: string, daily_tokens?: int} $settings
     */
    protected function enableAiFeature(string $key, array $settings = []): void
    {
        $features = json_decode((string) ($this->settings[FeatureSettings::SETTING] ?? '{}'), true) ?: [];
        $features[$key] = ['enabled' => true] + $settings;

        $this->setting(FeatureSettings::SETTING, json_encode($features));
    }

    /**
     * Grants a group the permission to use a feature.
     */
    protected function grantAiFeature(string $key, int $groupId): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => $groupId, 'permission' => 'ai.feature.'.$key]]]);
    }
}
