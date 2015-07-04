<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;

interface ActionInterface
{
    /**
     * Handle a request to the API, returning an HTTP response.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request);
}
