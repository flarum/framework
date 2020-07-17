<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertRegistered
{
    public function __invoke(Document $document, Request $request)
    {
        $request->getAttribute('actor')->assertRegistered();
    }
}
