<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api\Controller;

use Flarum\Ai\AiConfig;
use Flarum\Ai\Models\ModelCatalog;
use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The models a connection's provider offers, for the admin to choose from.
 * Kept for a day; `?refresh=1` asks the provider again.
 *
 * @internal
 */
class ListModelsController implements RequestHandlerInterface
{
    public function __construct(
        protected AiConfig $config,
        protected ModelCatalog $catalog
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $connection = (string) Arr::get($request->getQueryParams(), 'connection');

        if (! isset($this->config->connections()[$connection])) {
            throw new ModelNotFoundException();
        }

        $list = $this->catalog->get($connection, (bool) Arr::get($request->getQueryParams(), 'refresh'));

        return new JsonResponse(['connection' => $connection] + $list);
    }
}
