<?php namespace Flarum\Api\Actions;

use Flarum\Api\JsonApiRequest;
use Flarum\Api\Request;
use Tobscure\JsonApi\Document;

abstract class CreateAction extends SerializeResourceAction
{
    /**
     * Set a 201 Created status code on the response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function respond(Request $request)
    {
        return parent::respond($request)->withStatus(201);
    }

    /**
     * Get the newly created resource to be serialized and assigned to the response document.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return array
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->create($request);
    }

    /**
     * Create the resource.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Models\Model
     */
    abstract protected function create(JsonApiRequest $request);
}
