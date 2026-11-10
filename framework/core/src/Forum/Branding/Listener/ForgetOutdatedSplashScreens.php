<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding\Listener;

use Flarum\Forum\Branding\SplashScreen;
use Flarum\Settings\Event\Saved;

class ForgetOutdatedSplashScreens
{
    public function __construct(
        protected SplashScreen $splashScreen
    ) {
    }

    public function handle(Saved $event): void
    {
        if (array_intersect_key($event->settings, array_flip(['open_as_app', 'app_icon_background', 'splash_screen_background']))) {
            $this->splashScreen->forgetOutdated();
        }
    }
}
