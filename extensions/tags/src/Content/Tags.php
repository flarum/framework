<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Content;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\TagRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Tags
{
    public function __construct(
        protected Client $api,
        protected Factory $view,
        protected TagRepository $tags,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url
    ) {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $apiDocument = $this->getTagsDocument($request);
        $tags = collect((array) Arr::get($apiDocument, 'data', []));

        $childTags = $tags->where('attributes.isChild', true);
        $primaryTags = $tags->where('attributes.isChild', false)->where('attributes.position', '!==', null)->sortBy('attributes.position');
        $secondaryTags = $tags->where('attributes.isChild', false)->where('attributes.position', '===', null)->sortBy('attributes.name');

        $children = $primaryTags->mapWithKeys(function ($tag) use ($childTags) {
            $childIds = collect((array) Arr::get($tag, 'relationships.children.data'))->pluck('id');

            return [$tag['id'] => $childTags->whereIn('id', $childIds)->sortBy('position')];
        });

        $defaultRoute = $this->settings->get('default_route');
        $document->title = $this->translator->trans('flarum-tags.forum.all_tags.meta_title_text');
        $document->meta['description'] = $this->translator->trans('flarum-tags.forum.all_tags.meta_description_text');
        $document->content = $this->view->make('tags::frontend.content.tags', compact('primaryTags', 'secondaryTags', 'children'));
        $document->canonicalUrl = $this->url->to('forum')->base().($defaultRoute === '/tags' ? '' : $request->getUri()->getPath());
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    protected function getTagsDocument(Request $request): array
    {
        return json_decode(
            $this->api
                ->withoutErrorHandling()
                ->withParentRequest($request)
                ->withQueryParams([
                    'include' => 'children,lastPostedDiscussion,parent'
                ])
                ->get('/tags')
                ->getBody(),
            true
        );
    }
}
