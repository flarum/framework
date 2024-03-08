<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\JsonApi;
use Flarum\Api\Resource\ForumResource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowForumController implements RequestHandlerInterface
{
    public function __construct(
        protected JsonApi $api
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->api
            ->forResource(ForumResource::class)
            ->forEndpoint('show')
            ->handle($request);
    }
}
