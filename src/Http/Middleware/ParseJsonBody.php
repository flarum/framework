<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class ParseJsonBody implements MiddlewareInterface
{
    public function process(Request $request, DelegateInterface $delegate)
    {
        if (str_contains($request->getHeaderLine('content-type'), 'json')) {
            $input = json_decode($request->getBody(), true);

            $request = $request->withParsedBody($input ?: []);
        }

        return $delegate->process($request);
    }
}
