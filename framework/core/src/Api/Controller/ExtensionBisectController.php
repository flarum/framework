<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Extension\Bisect;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExtensionBisectController implements RequestHandlerInterface
{
    public function __construct(
        protected Bisect $bisect
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $issue = boolval(Arr::get($request->getParsedBody(), 'issue'));

        if (Arr::get($request->getParsedBody(), 'end')) {
            $this->bisect->end();

            return new JsonResponse([], 204);
        }

        $result = $this->bisect->break()->checkIssueUsing(function () use ($issue) {
            return $issue;
        })->run();

        return new JsonResponse($result ?? [], $result ? 200 : 204);
    }
}
