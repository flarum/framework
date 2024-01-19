<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use SessionHandlerInterface;

interface SessionDriverInterface
{
    /**
     * Build a session handler to handle sessions.
     * Settings and configuration can either be pulled from the Flarum settings repository
     * or the config.php file.
     *
     * @param SettingsRepositoryInterface $settings: An instance of the Flarum settings repository.
     * @param Config $config: An instance of the wrapper class around `config.php`.
     */
    public function build(SettingsRepositoryInterface $settings, Config $config): SessionHandlerInterface;
}
