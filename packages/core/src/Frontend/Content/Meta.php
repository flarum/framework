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

use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class Meta
{
    public function __invoke(Document $document, Request $request)
    {
        $document->meta = array_merge($document->meta, $this->buildMeta($document));
        $document->head = array_merge($document->head, $this->buildHead($document));
    }

    private function buildMeta(Document $document)
    {
        $forumApiDocument = $document->getForumApiDocument();

        $meta = [
            'viewport' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1',
            'description' => array_get($forumApiDocument, 'data.attributes.description'),
            'theme-color' => array_get($forumApiDocument, 'data.attributes.themePrimaryColor')
        ];

        return $meta;
    }

    private function buildHead(Document $document)
    {
        $head = [];

        if ($faviconUrl = array_get($document->getForumApiDocument(), 'data.attributes.faviconUrl')) {
            $head['favicon'] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        return $head;
    }
}
