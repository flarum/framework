<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Forum\Controller\FrontendController;

/**
 * @deprecated Will be removed in Beta.14. Use Flarum\Extend\Routes or Flarum\Extend\Frontend instead.
 */
class ConfigureForumRoutes extends AbstractConfigureRoutes
{
    /**
     * {@inheritdoc}
     */
    public function get($url, $name, $handler = FrontendController::class)
    {
        parent::get($url, $name, $handler);
    }
}
