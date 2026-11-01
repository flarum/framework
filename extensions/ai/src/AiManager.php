<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Laravel\Ai\AiManager as BaseAiManager;

/**
 * laravel/ai's manager, given the API key of a connection set up on the admin
 * page only when that connection's provider is first used. The rest of the
 * time the key stays in its table: it isn't in the config built on every
 * request.
 *
 * @internal
 */
class AiManager extends BaseAiManager
{
    public function getInstanceConfig($name): array
    {
        $config = parent::getInstanceConfig($name);

        if (! isset($config['key']) && $this->app->make(AiConfig::class)->isAdminConnection((string) $name)) {
            // Set even when there's none: providers that work without a key
            // still read it.
            $config['key'] = $this->app->make(ConnectionKeys::class)->get((string) $name);
        }

        return $config;
    }
}
