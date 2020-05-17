<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Post\Exception\FloodingException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class Floodgate implements Middleware
{
    protected $floodCheckers;

    public function __construct(array $floodCheckers)
    {
        $this->floodCheckers = $floodCheckers;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->isFlooding($request)) {
            throw new FloodingException;
        }

        return $handler->handle($request);
    }

    /**
     * @return bool
     */
    public function isFlooding(Request $request): bool
    {
        $actor = $request->getAttribute('actor');
        $path = $request->getAttribute('originalUri')->getPath();

        $isFlooding = false;

        foreach ($this->floodCheckers as $checker) {
            // We use fnmatch for paths but not methods to support paths that contain variables (like an id).
            $pathMatch = false;
            foreach ($checker['paths'] as $checkerPath) {
                if (fnmatch($checkerPath, $path)) {
                    $pathMatch = true;
                }
            }

            if ($pathMatch && in_array($request->getMethod(), $checker['methods'])) {
                $result = $checker['callback']($actor, $request);

                // Explicitly returning false overrides the floodgate.
                // Explicitly returning true marks the request as flooding.
                // Anything else is ignored.
                if ($result === false) {
                    return false;
                } elseif ($result === true) {
                    $isFlooding = true;
                }
            }
        }

        return $isFlooding;
    }
}
