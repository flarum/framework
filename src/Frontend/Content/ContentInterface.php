<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Frontend\FrontendView;
use Psr\Http\Message\ServerRequestInterface as Request;

interface ContentInterface
{
    /**
     * @param FrontendView $view
     * @param Request $request
     */
    public function populate(FrontendView $view, Request $request);
}
