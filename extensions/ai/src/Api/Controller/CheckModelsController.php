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
 * Checks the models in use now, rather than at the daily check. Says which
 * connections couldn't be asked, so a failed check doesn't look like a clean one.
 *
 * @internal
 */
class CheckModelsController implements RequestHandlerInterface
{
    public function __construct(
        protected ModelHealth $health
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return new JsonResponse($this->health->check());
    }
}
