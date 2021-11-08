<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Driver;

use Flarum\Frontend\Document;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class BasicTitleDriver implements TitleDriverInterface
{
    public function makeTitle(Document $document, ServerRequestInterface $request, array $forumApiDocument): string
    {
        $onHomePage = rtrim($request->getUri()->getPath(), '/') === '';

        return ($document->title && ! $onHomePage ? $document->title.' - ' : '').Arr::get($forumApiDocument, 'data.attributes.title');
    }
}
