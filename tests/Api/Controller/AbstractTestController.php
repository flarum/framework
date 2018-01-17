<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Flarum\Tests\Test\TestCase;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractTestController extends TestCase
{
    /**
     * @var ControllerInterface
     */
    protected $controller;

    /**
     * @var null|User
     */
    protected $actor = null;

    protected function callWith(array $body = []): ResponseInterface
    {
        $response = $this->call(
            $this->controller,
            $this->actor,
            [],
            $body ? ['data' => ['attributes' => $body]] : []
        );

        if ($response->getStatusCode() >= 500) {
            echo "\n\n-- api response error --\n";
            echo $response->getBody()->getContents();
            echo file_get_contents($this->app->storagePath() . '/logs/flarum.log');
            echo "\n-- --\n\n";
        }

        return $response;
    }
}
