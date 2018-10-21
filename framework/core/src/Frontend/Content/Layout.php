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

class Layout implements ContentInterface
{
    /**
     * @var string
     */
    protected $layoutView;

    /**
     * @param string $layoutView
     */
    public function __construct(string $layoutView)
    {
        $this->layoutView = $layoutView;
    }

    public function __invoke(HtmlDocument $document, Request $request)
    {
        $document->layoutView = $this->layoutView;
    }
}
