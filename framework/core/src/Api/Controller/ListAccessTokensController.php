<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\AccessTokenSerializer;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListAccessTokensController extends AbstractListController
{
    public ?string $serializer = AccessTokenSerializer::class;

    public function __construct(
        protected UrlGenerator $url,
        protected SearchManager $search
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $offset = $this->extractOffset($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        $tokens = $this->search->query(AccessToken::class, new SearchCriteria($actor, $filter, $limit, $offset));

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
