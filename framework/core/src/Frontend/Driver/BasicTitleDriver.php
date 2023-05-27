<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Driver;

use Flarum\Frontend\Document;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class BasicTitleDriver implements TitleDriverInterface
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function makeTitle(Document $document, ServerRequestInterface $request, array $forumApiDocument): string
    {
        $onHomePage = rtrim($request->getUri()->getPath(), '/') === '';

        $params = [
            'pageTitle' => $document->title ?? '',
            'forumName' => Arr::get($forumApiDocument, 'data.attributes.title'),
            'pageNumber' => $document->page ?? 1,
        ];

        return $onHomePage || ! $document->title
            ? $this->translator->trans('core.lib.meta_titles.without_page_title', $params)
            : $this->translator->trans('core.lib.meta_titles.with_page_title', $params);
    }
}
