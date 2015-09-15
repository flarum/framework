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

use Flarum\Api\Request;
use Zend\Diactoros\Response\EmptyResponse;

abstract class DeleteAction implements Action
{
    /**
     * Delegate deletion of the resource, and return a 204 No Content
     * response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
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
