<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\Exception\ProxyNotAllowedException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ProxyAddress implements Middleware
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var array
     */
    protected $allowedAddresses;

    /**
     * @param bool $enabled
     * @param array $allowedAddresses
     */
    public function __construct($enabled, $allowedAddresses)
    {
        $this->enabled = $enabled;
        $this->allowedAddresses = $allowedAddresses;
    }

    private function wildcardMatch(string $ipAddress): bool
    {
        foreach ($this->allowedAddresses as $allowedAddress) {
            if (fnmatch($allowedAddress, $ipAddress)) {
                return true;
            }
        }

        return false;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ipAddress = Arr::get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        if ($this->enabled) {
            if ($this->wildcardMatch($ipAddress)) {
                // standard header for proxies, see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For
                $ipAddress = Arr::get($request->getServerParams(), 'X_FORWARDED_FOR', $ipAddress);
                $ipAddress = Arr::get($request->getServerParams(), 'HTTP_CLIENT_IP', $ipAddress);
            } else {
                throw new ProxyNotAllowedException("The used proxy isn't allowed to connect!");
            }
        }

        $request = $request->withAttribute('ipAddress', $ipAddress);

        return $handler->handle($request);
    }
}
