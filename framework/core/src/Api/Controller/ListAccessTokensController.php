<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\AccessTokenSerializer;
use Flarum\Http\Filter\AccessTokenFilterer;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Query\QueryCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListAccessTokensController extends AbstractListController
{
    public $serializer = AccessTokenSerializer::class;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var AccessTokenFilterer
     */
    protected $filterer;

    public function __construct(UrlGenerator $url, AccessTokenFilterer $filterer)
    {
        $this->url = $url;
        $this->filterer = $filterer;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $offset = $this->extractOffset($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        $tokens = $this->filterer->filter(new QueryCriteria($actor, $filter), $limit, $offset);

        $document->addPaginationLinks(
            $this->url->to('api')->route('access-tokens.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $tokens->areMoreResults() ? null : 0
        );

        return $tokens->getResults();
    }
}
