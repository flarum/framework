<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Forum\Controller\FrontendController;

/**
 * @deprecated
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
