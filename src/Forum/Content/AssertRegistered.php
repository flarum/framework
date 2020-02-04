<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Frontend\Document;
use Flarum\User\AssertPermissionTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertRegistered
{
    use AssertPermissionTrait;

    public function __invoke(Document $document, Request $request)
    {
        $this->assertRegistered($request->getAttribute('actor'));
    }
}
