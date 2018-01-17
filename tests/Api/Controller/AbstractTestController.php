<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Flarum\Tests\Test\TestCase;
use Flarum\User\User;

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

    protected function callWith(array $body = [])
    {
        return $this->call(
            $this->controller,
            $this->actor,
            [],
            $body ? ['data' => ['attributes' => $body]] : []
        );
    }
}
