<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api\Controller;

use Flarum\Ai\AdminConnections;
use Flarum\Ai\AiManager;
use Flarum\Ai\Exception\ModelsUnavailableException;
use Flarum\Ai\Models\ModelLister;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Laravel\Ai\Providers\Provider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * The models a connection the admin is adding or changing offers, before it's
 * saved, so they can choose its default model from the list. Not kept: the
 * connection may never be saved as it is.
 *
 * @internal
 */
class ListCandidateModelsController implements RequestHandlerInterface
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiManager $manager,
        protected ModelLister $lister
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $body = (array) $request->getParsedBody();
        $name = (string) Arr::get($body, 'name');
        $candidate = $this->connections->candidate($name, Arr::get($body, 'connection'));
        $driver = $candidate['driver'];

        if (! $this->lister->canList($driver)) {
            return new JsonResponse(['models' => null]);
        }

        $models = $this->manager->withCandidate($name, $candidate, function () use ($name, $driver) {
            try {
                /** @var Provider $provider */
                $provider = $this->manager->instance($name);

                return $this->lister->list($driver, $provider);
            } catch (Throwable $e) {
                throw new ModelsUnavailableException($name, $e);
            }
        });

        return new JsonResponse(['models' => $models]);
    }
}
