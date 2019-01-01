<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Flarum\Api\Client;
use Flarum\User\Guest;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

trait MakesApiRequests
{
    public function call(string $controller, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        /** @var Client $api */
        $api = app(Client::class);

        $api->setErrorHandler(null);

        return $api->send($controller, $actor ?? new Guest, $queryParams, $body);
    }
}
