<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api\Controller;

use Flarum\Ai\Models\ModelHealth;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The model issues as they are now, without checking again: for the admin
 * page once a save has changed which models are in use.
 *
 * @internal
 */
class ShowModelIssuesController implements RequestHandlerInterface
{
    public function __construct(
        protected ModelHealth $health
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return new JsonResponse(['issues' => $this->health->forAdmin()]);
    }
}
