<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Test\Concerns;

use Flarum\Api\ApiServiceProvider;
use Flarum\Api\Client;
use Flarum\User\Guest;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

trait MakesApiRequests
{
    public function call(string $controller, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        $this->app->register(ApiServiceProvider::class);
        $this->app->make('flarum.api.middleware');
        /** @var Client $api */
        $api = $this->app->make(Client::class);

        return $api->send($controller, $actor ?? new Guest, $queryParams, $body);
    }
}
