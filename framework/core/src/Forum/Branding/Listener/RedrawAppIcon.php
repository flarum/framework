<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding\Listener;

use Flarum\Forum\Branding\AppIcon;
use Flarum\Settings\Event\Saved;

class RedrawAppIcon
{
    public function __construct(
        protected AppIcon $appIcon
    ) {
    }

    public function handle(Saved $event): void
    {
        if (array_key_exists('app_icon_background', $event->settings)) {
            $this->appIcon->draw();
        }
    }
}
