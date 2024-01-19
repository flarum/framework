<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Tag;
use Flarum\Tags\TagRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTagsController extends AbstractListController
{
    public ?string $serializer = TagSerializer::class;

    public array $include = [
        'parent'
    ];

    public array $optionalInclude = [
        'children',
        'lastPostedDiscussion',
        'state'
    ];

    public function __construct(
        protected TagRepository $tags,
        protected SearchManager $search,
        protected UrlGenerator $url
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);
        $include = $this->extractInclude($request);
        $filters = $this->extractFilter($request);
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        if (in_array('lastPostedDiscussion', $include)) {
            $include = array_merge($include, ['lastPostedDiscussion.tags', 'lastPostedDiscussion.state']);
        }

        if (array_key_exists('q', $filters)) {
            $results = $this->search->query(Tag::class, new SearchCriteria($actor, $filters, $limit, $offset));

            $tags = $results->getResults();

            $document->addPaginationLinks(
                $this->url->to('api')->route('tags.index'),
                $request->getQueryParams(),
                $offset,
                $limit,
                $results->areMoreResults() ? null : 0
            );
        } else {
            $tags = $this->tags
                ->with($include, $actor)
                ->whereVisibleTo($actor)
                ->withStateFor($actor)
                ->get();
        }

        return $tags;
    }
}
