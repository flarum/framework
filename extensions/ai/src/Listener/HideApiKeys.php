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
use Flarum\Settings\Event\Deserializing;

/**
 * The admin page is sent every setting, so the stored connections are swapped
 * for a copy that says whether each has a key instead of what it is.
 */
class HideApiKeys
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiConfig $config
    ) {
    }

    public function handle(Deserializing $event): void
    {
        if (! array_key_exists(AdminConnections::SETTING, $event->settings)) {
            return;
        }

        $event->settings[AdminConnections::SETTING] = json_encode(
            (object) $this->connections->withoutKeys($this->config->adminConnections()),
            JSON_UNESCAPED_SLASHES
        );
    }
}
