<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Forum\Branding\AppIcon;
use Flarum\Forum\Branding\ThemeImages;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAppIconController extends AbstractDeleteController
{
    public function __construct(
        protected AppIcon $appIcon,
        protected ThemeImages $themeImages
    ) {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->themeImages->assertNotSet('app_icon', 'app-icon');

        $this->appIcon->delete();
    }
}
