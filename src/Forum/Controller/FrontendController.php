<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\ForumFrontend;
use Flarum\Frontend\AbstractFrontendController;
use Illuminate\Contracts\Events\Dispatcher;

class FrontendController extends AbstractFrontendController
{
    /**
     * @param ForumFrontend $frontend
     * @param Dispatcher $events
     */
    public function __construct(ForumFrontend $frontend, Dispatcher $events)
    {
        parent::__construct($frontend, $events);
    }
}
