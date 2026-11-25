<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Forum\Controller;

use Flarum\Forum\Controller\LogOutController;
use Flarum\Forum\Controller\LogOutViewController;
use Flarum\Foundation\Config;
use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\RouteCollectionUrlGenerator;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\Testing\unit\TestCase;
use Flarum\User\Guest;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutReturnUrlTest extends TestCase
{
    public static function controllers(): array
    {
        return [
            'LogOutController' => ['logOutController'],
            'LogOutViewController' => ['logOutViewController'],
        ];
    }

    #[Test]
    #[DataProvider('controllers')]
    public function a_subclass_overriding_the_allowed_domains_is_honoured(string $factory)
    {
        $controller = $this->$factory(['sso.example']);

        $this->assertEquals('https://sso.example/bye', $this->redirectFor($controller, 'https://sso.example/bye'));
        $this->assertEquals('http://flarum.test', $this->redirectFor($controller, 'http://flarum.test/d/1'));
    }

    #[Test]
    #[DataProvider('controllers')]
    public function the_supplied_config_decides_the_default_allowed_domains(string $factory)
    {
        $controller = $this->$factory(null, ['redirectDomains' => ['idp.example']]);

        $this->assertEquals('https://idp.example/x', $this->redirectFor($controller, 'https://idp.example/x'));
        $this->assertEquals('http://flarum.test', $this->redirectFor($controller, 'https://evil.example/'));
    }

    protected function redirectFor(RequestHandlerInterface $controller, string $return): string
    {
        $request = RequestUtil::withActor(
            (new ServerRequest())->withQueryParams(['return' => $return]),
            new Guest()
        );

        return $controller->handle($request)->getHeaderLine('Location');
    }

    protected function config(array $extra): Config
    {
        return new Config(['url' => 'http://flarum.test'] + $extra);
    }

    protected function url(): UrlGenerator
    {
        $forum = $this->createStub(RouteCollectionUrlGenerator::class);
        $forum->method('base')->willReturn('http://flarum.test');

        $url = $this->createStub(UrlGenerator::class);
        $url->method('to')->willReturn($forum);

        return $url;
    }

    protected function logOutController(?array $allowed, array $config = []): LogOutController
    {
        $args = [
            $this->createStub(Dispatcher::class),
            $this->createStub(SessionAuthenticator::class),
            $this->createStub(Rememberer::class),
            $this->url(),
            $this->config($config),
        ];

        return $allowed === null ? new LogOutController(...$args) : new class($allowed, ...$args) extends LogOutController {
            public function __construct(private array $allowed, ...$args)
            {
                parent::__construct(...$args);
            }

            protected function getAllowedRedirectDomains(): array
            {
                return $this->allowed;
            }
        };
    }

    protected function logOutViewController(?array $allowed, array $config = []): LogOutViewController
    {
        $args = [$this->createStub(Factory::class), $this->url(), $this->config($config)];

        return $allowed === null ? new LogOutViewController(...$args) : new class($allowed, ...$args) extends LogOutViewController {
            public function __construct(private array $allowed, ...$args)
            {
                parent::__construct(...$args);
            }

            protected function getAllowedRedirectDomains(): array
            {
                return $this->allowed;
            }
        };
    }
}
