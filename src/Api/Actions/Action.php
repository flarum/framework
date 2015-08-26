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

interface Action
{
    /**
     * Handle a request to the API, returning an HTTP response.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request);
}
