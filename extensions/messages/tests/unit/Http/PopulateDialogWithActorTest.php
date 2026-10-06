<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\unit\Http;

use Flarum\Http\RequestUtil;
use Flarum\Messages\Dialog;
use Flarum\Messages\Http\Middleware\PopulateDialogWithActor;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The actor a dialog's read state is resolved for is a static. A request
 * made from inside another (realtime's per-recipient payloads) must not leave
 * the inner actor behind, or the outer response resolves state for the wrong
 * member and shows one person another's read receipts.
 */
class PopulateDialogWithActorTest extends TestCase
{
    protected function tearDown(): void
    {
        Dialog::setStateUser(null);

        parent::tearDown();
    }

    private function handlerAsserting(User $expected): RequestHandlerInterface
    {
        return new class($expected) implements RequestHandlerInterface {
            public function __construct(private User $expected)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (Dialog::stateUser() !== $this->expected) {
                    throw new \RuntimeException('State user was not set for the request');
                }

                return new Response();
            }
        };
    }

    #[Test]
    public function sets_the_actor_for_the_request_and_puts_the_previous_one_back_afterwards(): void
    {
        $outer = $this->createMock(User::class);
        $inner = $this->createMock(User::class);

        Dialog::setStateUser($outer);

        $request = RequestUtil::withActor(new ServerRequest([], [], '/api/dialogs', 'GET'), $inner);

        (new PopulateDialogWithActor())->process($request, $this->handlerAsserting($inner));

        $this->assertSame($outer, Dialog::stateUser());
    }

    #[Test]
    public function puts_the_previous_actor_back_even_when_the_request_throws(): void
    {
        $outer = $this->createMock(User::class);
        $inner = $this->createMock(User::class);

        Dialog::setStateUser($outer);

        $request = RequestUtil::withActor(new ServerRequest([], [], '/api/dialogs', 'GET'), $inner);
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('boom');
            }
        };

        try {
            (new PopulateDialogWithActor())->process($request, $handler);
        } catch (\RuntimeException) {
        }

        $this->assertSame($outer, Dialog::stateUser());
    }
}
