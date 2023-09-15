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
use Flarum\Query\QueryCriteria;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Search\TagSearcher;
use Flarum\Tags\TagRepository;
use Illuminate\Http\Request;
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
        protected TagSearcher $searcher,
        protected UrlGenerator $url
    ) {
    }

    protected function data(Request $request, Document $document): iterable
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
            $results = $this->searcher->search(new QueryCriteria($actor, $filters), $limit, $offset);
            $tags = $results->getResults();

            $document->addPaginationLinks(
                $this->url->route('api.tags.index'),
                $request->query(),
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
