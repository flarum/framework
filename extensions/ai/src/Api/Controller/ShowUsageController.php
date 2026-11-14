<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api\Controller;

use Flarum\Ai\Usage\Ledger;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The usage totals the AI admin page shows.
 *
 * @internal
 */
class ShowUsageController implements RequestHandlerInterface
{
    public function __construct(
        protected Ledger $ledger
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return new JsonResponse($this->ledger->summary());
    }
}
