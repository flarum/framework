<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Zend\Diactoros\Response\EmptyResponse;

abstract class DeleteAction extends JsonApiAction
{
    /**
     * Delegate deletion of the resource, and return a 204 No Content
     * response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function respond(Request $request)
    {
        $this->delete($request);

        return new EmptyResponse(204);
    }

    /**
     * Delete the resource.
     *
     * @param \Flarum\Api\Request $request
     * @return void
     */
    abstract protected function delete(Request $request);
}
