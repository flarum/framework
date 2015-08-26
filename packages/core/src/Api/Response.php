<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getBody()
    {
        return json_decode($this->response->getBody());
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }
}
