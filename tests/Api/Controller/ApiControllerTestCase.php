<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Flarum\Tests\Test\TestCase;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

abstract class ApiControllerTestCase extends TestCase
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
        return $this->call(
            $this->controller,
            $this->actor,
            [],
            $body ? ['data' => ['attributes' => $body]] : []
        );
    }

    protected function tearDown()
    {
        $this->actor = null;
        parent::tearDown();
    }
}
