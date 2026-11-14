<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Forum\Content;

use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertCanUseDeck
{
    public function __invoke(Document $document, Request $request): void
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();
        $actor->assertCan('deck.use');
    }
}
