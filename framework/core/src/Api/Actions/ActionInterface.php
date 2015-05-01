<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;

interface ActionInterface
{
    /**
     * Handle a request to the API, returning an HTTP response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request);
}
