<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions;

use Flarum\Api\JsonApiRequest;
use Flarum\Api\Request;
use Tobscure\JsonApi\Document;

abstract class CreateAction extends SerializeResourceAction
{
    /**
     * Set a 201 Created status code on the response.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function respond(Request $request)
    {
        return parent::respond($request)->withStatus(201);
    }

    /**
     * Get the newly created resource to be serialized and assigned to the response document.
     *
     * @param JsonApiRequest $request
     * @param Document $document
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
     * @return \Flarum\Core\Model
     */
    abstract protected function create(JsonApiRequest $request);
}
