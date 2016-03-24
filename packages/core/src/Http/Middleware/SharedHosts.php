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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class SharedHosts implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $SERVER = $request->getServerParams();

        // CGI wrap may not pass on the Authorization header.
        // In that case, the web server can be configured
        // to pass its value in an env variable instead.
        if (isset($SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $request = $request->withHeader('authorization', $SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        return $out ? $out($request, $response) : $response;
    }
}
