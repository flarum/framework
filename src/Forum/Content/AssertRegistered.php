<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Frontend\Content\ContentInterface;
use Flarum\Frontend\HtmlDocument;
use Flarum\User\AssertPermissionTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertRegistered implements ContentInterface
{
    use AssertPermissionTrait;

    public function populate(HtmlDocument $document, Request $request)
    {
        $this->assertRegistered($request->getAttribute('actor'));
    }
}
