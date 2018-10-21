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

use Flarum\Frontend\HtmlDocument;
use Psr\Http\Message\ServerRequestInterface as Request;

interface ContentInterface
{
    /**
     * @param HtmlDocument $document
     * @param Request $request
     */
    public function __invoke(HtmlDocument $document, Request $request);
}
