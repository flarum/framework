<?php

namespace Flarum\Tests\Test\Concerns;

use Flarum\Api\Client;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

trait MakesApiRequests
{
    public function call(string $controller, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        /** @var Client $api */
        $api = $this->app->make(Client::class);

        return $api->send($controller, $actor, $queryParams, $body);
    }
}
