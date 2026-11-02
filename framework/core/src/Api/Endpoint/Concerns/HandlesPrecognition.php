<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Api\Context;
use Flarum\Api\Precognition;
use Flarum\Api\Resource\AbstractResource;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Exception\MethodNotAllowedException;

/**
 * The endpoint's action is never invoked for a precognitive request, so its
 * hooks, the field setters, the resource's saving hooks and events never run,
 * even when an extension replaced the action. A `204` means the fields are
 * valid and the endpoint is visible to the actor, not that the write would
 * succeed: authorization that lives in hooks or setters (e.g. the
 * `editCredentials` check on `UserResource`'s email setter) is skipped.
 *
 * @see Precognition
 */
trait HandlesPrecognition
{
    /**
     * Must not have side effects.
     */
    abstract protected function validatePrecognitive(Context $context): void;

    /**
     * @param Context $context
     */
    public function handle(\Tobyz\JsonApiServer\Context $context): ?Response
    {
        if (! Precognition::active($context->request)) {
            return parent::handle($context);
        }

        // Mirrors Endpoint::handle() up to the action call.
        if (strtolower($context->method()) !== strtolower($this->method)) {
            throw new MethodNotAllowedException();
        }

        /** @var AbstractResource $collection */
        $collection = $context->collection;

        $context = $context->withModelId($collection->id($context));

        if ($context->modelId !== null && $context->modelId !== '') {
            $context = $context->withModel($this->findResource($context, $context->modelId));
        }

        if (! $this->isVisible($context)) {
            throw new ForbiddenException();
        }

        $this->validatePrecognitive($context);

        return new EmptyResponse(204, [Precognition::SUCCESS_HEADER => 'true']);
    }

    /**
     * @return string[]|null
     */
    protected function precognitiveFields(Context $context): ?array
    {
        return Precognition::validateOnly($context->request);
    }
}
