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
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTagsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = TagSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'parent'
    ];

    /**
     * {@inheritdoc}
     */
    public $optionalInclude = [
        'children',
        'lastPostedDiscussion',
        'state'
    ];

    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @var TagSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(TagRepository $tags, TagSearcher $searcher, UrlGenerator $url)
    {
        $this->tags = $tags;
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
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
