<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Content;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\TagRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Tags
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Client $api
     * @param Factory $view
     * @param TagRepository $tags
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(Client $api, Factory $view, TagRepository $tags, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->api = $api;
        $this->view = $view;
        $this->tags = $tags;
        $this->settings = $settings;
        $this->url = $url;
    }

    public function __invoke(Document $document, Request $request)
    {
        $tags = collect($document->payload['resources'])->where('type', 'tags');
        $childTags = $tags->where('attributes.isChild', true);
        $primaryTags = $tags->where('attributes.isChild', false)->where('attributes.position', '!==', null)->sortBy('attributes.position');
        $secondaryTags = $tags->where('attributes.isChild', false)->where('attributes.position', '===', null)->sortBy('attributes.name');
        $defaultRoute = $this->settings->get('default_route');

        $children = $primaryTags->mapWithKeys(function ($tag) use ($childTags) {
            $id = Arr::get($tag, 'id');

            return [
                $id => $childTags->where('relationships.parent.data.id', $id)->pluck('attributes')->sortBy('position')
            ];
        });

        $document->content = $this->view->make('tags::frontend.content.tags', compact('primaryTags', 'secondaryTags', 'children'));
        $document->canonicalUrl = $defaultRoute === '/tags' ? $this->url->to('forum')->base() : $request->getUri()->withQuery('');

        return $document;
    }
}
