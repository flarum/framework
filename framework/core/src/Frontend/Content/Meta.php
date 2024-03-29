<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Frontend\Document;
use Flarum\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Meta
{
    public function __construct(
        private readonly LocaleManager $locales
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->language = $this->locales->getLocale();
        $document->direction = 'ltr';

        $document->meta = array_merge($document->meta, $this->buildMeta($document));
        $document->head = array_merge($document->head, $this->buildHead($document));
    }

    private function buildMeta(Document $document): array
    {
        $forumApiDocument = $document->getForumApiDocument();

        return [
            'viewport' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1',
            'description' => Arr::get($forumApiDocument, 'data.attributes.description'),
            'theme-color' => Arr::get($forumApiDocument, 'data.attributes.themePrimaryColor')
        ];
    }

    private function buildHead(Document $document): array
    {
        $head = [];

        if ($faviconUrl = Arr::get($document->getForumApiDocument(), 'data.attributes.faviconUrl')) {
            $head['favicon'] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        return $head;
    }
}
