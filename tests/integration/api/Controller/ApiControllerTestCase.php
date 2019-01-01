<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class ApiControllerTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @var RequestHandlerInterface
     */
    protected $controller;

    /**
     * @var null|User
     */
    protected $actor = null;

    protected function callWith(array $body = [], array $queryParams = []): ResponseInterface
    {
        if (! Arr::get($body, 'data') && Arr::isAssoc($body)) {
            $body = ['data' => ['attributes' => $body]];
        }

        return $this->call(
            $this->controller,
            $this->actor,
            $queryParams,
            $body
        );
    }

    protected function tearDown()
    {
        $this->actor = null;
        parent::tearDown();
    }
}
