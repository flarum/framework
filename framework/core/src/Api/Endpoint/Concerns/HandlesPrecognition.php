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
 * Answers precognitive (validate-only) requests for a saving endpoint.
 *
 * The endpoint's action closure is never invoked for such a request, so
 * neither its before/after hooks, the field setters, the resource's
 * creating/updating/saving/saved hooks, the save itself nor the event
 * dispatch can run, even when an extension replaced the action.
 *
 * A `204` therefore means the fields passed validation and the endpoint is
 * visible to the actor, not that the write would succeed: authorization that
 * lives in those hooks and setters (e.g. GDPR's password check, the messages
 * extension's `sendAnyMessage`, `UserResource`'s `editCredentials` check on
 * the email setter) is not run.
 *
 * @see Precognition
 */
trait HandlesPrecognition
{
    /**
     * Run authorization and validation only, without side effects.
     *
     * @throws \Throwable on authorization or validation failure.
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

        // Same request preparation as Endpoint::handle(): resolve the model
        // (404 when missing or invisible) and check the endpoint's own
        // authorization (authenticated/admin/can/visible → 401/403).
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
     * The field names the client limited validation to, or null for all.
     *
     * @return string[]|null
     */
    protected function precognitiveFields(Context $context): ?array
    {
        return Precognition::validateOnly($context->request);
    }
}
