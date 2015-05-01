<?php namespace Flarum\Api\Actions;

use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

abstract class CreateAction extends SerializeResourceAction
{
    /**
     * Delegate creation of the resource, and set a 201 Created status code on
     * the response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Model
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $response->setStatusCode(201);

        return $this->create($request, $response);
    }

    /**
     * Create the resource.
     *
     * @return \Flarum\Core\Models\Model
     */
    abstract protected function create(JsonApiRequest $request, JsonApiResponse $response);
}
