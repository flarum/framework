<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Illuminate\Http\Response;

abstract class DeleteAction implements ActionInterface
{
    /**
     * Delegate deletion of the resource, and return a 204 No Content
     * response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Flarum\Api\Response
     */
    public function handle(Request $request)
    {
        $this->delete($request, $response = new Response('', 204));

        return $response;
    }

    /**
     * Delete the resource.
     *
     * @param \Flarum\Api\Request $request
     * @param \Flarum\Api\Response $response
     * @return void
     */
    abstract protected function delete(Request $request, Response $response);
}
