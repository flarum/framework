<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Forum\Branding\SplashScreen;
use Flarum\Forum\Branding\WebManifest;
use Flarum\Frontend\Document;

/**
 * The tags that open the forum as an app from the home screen. Only the forum
 * gets them: the admin frontend opens in the browser.
 */
class HomeScreenApp
{
    public function __construct(
        protected WebManifest $manifest,
        protected SplashScreen $splashScreen
    ) {
    }

    public function __invoke(Document $document): void
    {
        if (! $this->manifest->opensAsApp()) {
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
