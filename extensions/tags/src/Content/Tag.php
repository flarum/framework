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
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Tags\Tag as TagModel;
use Flarum\Tags\TagRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class Tag
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SlugManager
     */
    protected $slugger;

    public function __construct(
        Client $api,
        Factory $view,
        TagRepository $tags,
        TranslatorInterface $translator,
        SlugManager $slugger
    ) {
        $this->api = $api;
        $this->view = $view;
        $this->tags = $tags;
        $this->translator = $translator;
        $this->slugger = $slugger;
    }

    public function __invoke(Document $document, Request $request)
    {
        $queryParams = $request->getQueryParams();
        $actor = RequestUtil::getActor($request);

        $slug = Arr::pull($queryParams, 'slug');
        $sort = Arr::pull($queryParams, 'sort');
        $q = Arr::pull($queryParams, 'q', '');
        $page = max(1, intval(Arr::pull($queryParams, 'page')));
        $filters = Arr::pull($queryParams, 'filter', []);

        $sortMap = $this->getSortMap();

        $tag = $this->slugger->forResource(TagModel::class)->fromSlug($slug, $actor);

        $params = [
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '',
            'filter' => [
                'tag' => "$slug"
            ],
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        $params['filter'] = array_merge($filters, $params['filter']);

        $apiDocument = $this->getApiDocument($request, $params);

        $tagsDocument = $this->getTagsDocument($request, $slug);

        $apiDocument->included[] = $tagsDocument->data;
        $includedTags = $tagsDocument->included ?? [];
        foreach ((array) $includedTags as $includedTag) {
            $apiDocument->included[] = $includedTag;
        }

        $document->title = $tag->name;
        if ($tag->description) {
            $document->meta['description'] = $tag->description;
        } else {
            $document->meta['description'] = $this->translator->trans('flarum-tags.forum.tag.meta_description_text', ['{tag}' => $tag->name]);
        }
        $document->content = $this->view->make('tags::frontend.content.tag', compact('apiDocument', 'page', 'tag'));
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get a map of sort query param values and their API sort params.
     *
     * @return array
     */
    protected function getSortMap()
    {
        return resolve('flarum.forum.discussions.sortmap');
    }

    /**
     * Get the result of an API request to list discussions.
     */
    protected function getApiDocument(Request $request, array $params)
    {
        return json_decode($this->api->withParentRequest($request)->withQueryParams($params)->get('/discussions')->getBody());
    }

    protected function getTagsDocument(Request $request, string $slug)
    {
        return json_decode($this->api->withParentRequest($request)->withQueryParams([
            'include' => 'children,children.parent,parent,parent.children.parent,state'
        ])->get("/tags/$slug")->getBody());
    }
}
