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
use Zend\Diactoros\Response\JsonResponse;

abstract class JsonApiAction implements Action
{
    /**
     * Handle an API request and return an API response, handling any relevant
     * (API-related) exceptions that are thrown.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
    {
        return $this->respond($request);
    }

    /**
     * Handle an API request and return an API response.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respond(Request $request);
}
