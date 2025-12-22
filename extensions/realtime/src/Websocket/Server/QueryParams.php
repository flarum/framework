<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Server;

use Psr\Http\Message\RequestInterface;

class QueryParams
{
    public static function create(RequestInterface $request): self
    {
        return new static($request);
    }

    /**
     * Initialize the class.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function __construct(protected RequestInterface $request)
    {
    }

    /**
     * Get all query parameters.
     *
     * @return array
     */
    public function all(): array
    {
        $queryParameters = [];

        parse_str($this->request->getUri()->getQuery(), $queryParameters);

        return $queryParameters;
    }

    /**
     * Get a specific query parameter.
     *
     * @param  string  $name
     * @return string
     */
    public function get(string $name): string
    {
        return $this->all()[$name] ?? '';
    }
}
