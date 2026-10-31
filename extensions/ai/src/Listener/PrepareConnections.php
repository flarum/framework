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
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Runs before any setting in the save is written, so a rejected connection
 * leaves every setting as it was.
 */
class PrepareConnections
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiConfig $config,
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

        if (array_key_exists(AdminConnections::DEFAULT_SETTING, $event->settings)) {
            $this->connections->assertDefaultExists(
                $event->settings[AdminConnections::DEFAULT_SETTING],
                [...$adminNames, ...array_keys($this->config->lockedConnections())]
            );
        }
    }
}
