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
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListAccessTokensController extends AbstractListController
{
    public $serializer = AccessTokenSerializer::class;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
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

        $tokens = AccessToken::query()
            ->whereVisibleTo($actor)
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        if ($areMoreResults = ($limit > 0 && $tokens->count() > $limit)) {
            $tokens->pop();
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('access-tokens.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $areMoreResults
        );

        return $tokens;
    }
}
