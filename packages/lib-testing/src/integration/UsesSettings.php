<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Flarum\Settings\SettingsRepositoryInterface;

trait UsesSettings
{
    /**
     * Removes the settings respository instance from the IoC container.
     *
     * This allows test cases that add/modify settings to refresh the in-memory settings cache.
     */
    protected function purgeSettingsCache()
    {
        $this->app()->getContainer()->forgetInstance(SettingsRepositoryInterface::class);
    }
}
