<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Franzl\Middleware\Whoops\WhoopsRunner;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WhoopsRenderer implements Formatter
{
    public function format(HandledError $error, Request $request): Response
    {
        return WhoopsRunner::handle($error->getError(), $request)
            ->withStatus($error->getStatusCode());
    }
}
