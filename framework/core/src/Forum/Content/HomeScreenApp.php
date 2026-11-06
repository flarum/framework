<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Forum\Branding\Branding;
use Flarum\Forum\Branding\SplashScreen;
use Flarum\Forum\Branding\WebManifest;
use Flarum\Frontend\Document;

/**
 * The tags that open the forum as an app from the home screen. Only the forum
 * gets them, and only with an app icon: without one, a phone would put a
 * screenshot of the page on the home screen. The admin frontend opens in the
 * browser.
 */
class HomeScreenApp
{
    public function __construct(
        protected WebManifest $manifest,
        protected SplashScreen $splashScreen,
        protected Branding $branding
    ) {
    }

    public function __invoke(Document $document): void
    {
        if (! $this->manifest->opensAsApp() || $this->branding->appIconPaths() === []) {
            return;
        }

        $document->meta['apple-mobile-web-app-capable'] = 'yes';
        $document->meta['mobile-web-app-capable'] = 'yes';

        if ($links = $this->splashScreen->links()) {
            $document->head['apple-touch-startup-image'] = implode("\n", array_map(
                fn (array $link) => '<link rel="apple-touch-startup-image" href="'.e($link['href']).'" media="'.e($link['media']).'">',
                $links
            ));
        }
    }
}
