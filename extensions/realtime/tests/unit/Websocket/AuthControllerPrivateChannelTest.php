<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Http\RequestUtil;
use Flarum\Realtime\Push\Payload\Generator;
use Flarum\Realtime\Websocket\Api\AuthController;
use Flarum\Realtime\Websocket\Api\PresenceChannelAuthorizer;
use Flarum\Realtime\Websocket\Api\PrivateChannelAuthorizer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pusher\Pusher;

/**
 * An extension's private channel is authorized only under the one spelling of
 * its name that the extension's pushes will use.
 */
class AuthControllerPrivateChannelTest extends TestCase
{
    /** @var int[] */
    private array $seen = [];

    private function statusFor(string $channel): int
    {
        $authorizer = new PrivateChannelAuthorizer();
        $authorizer->add('postEdit', function (User $actor, int $id) {
            $this->seen[] = $id;

            return $id === 7;
        });

        $controller = new AuthController(
            new Pusher('key', 'secret', '1'),
            $this->createStub(Generator::class),
            new PresenceChannelAuthorizer(),
            $authorizer,
            $this->createStub(SettingsRepositoryInterface::class)
        );

        $request = RequestUtil::withActor(
            (new ServerRequest())->withParsedBody(['channel_name' => $channel, 'socket_id' => '123.456']),
            $this->createStub(User::class)
        );

        return $controller->handle($request)->getStatusCode();
    }

    #[Test]
    public function the_canonical_name_is_authorized(): void
    {
        $this->assertSame(200, $this->statusFor('private-postEdit=7'));
        $this->assertSame([7], $this->seen);
    }

    public static function otherSpellings(): array
    {
        return [
            'leading zero' => ['private-postEdit=07'],
            'several leading zeros' => ['private-postEdit=0007'],
            'trailing newline' => ["private-postEdit=7\n"],
        ];
    }

    #[Test]
    #[DataProvider('otherSpellings')]
    public function another_spelling_of_the_same_id_is_denied_without_asking_the_guard(string $channel): void
    {
        $this->assertSame(403, $this->statusFor($channel));
        $this->assertSame([], $this->seen);
    }

    #[Test]
    public function id_zero_is_still_passed_to_the_guard(): void
    {
        $this->assertSame(403, $this->statusFor('private-postEdit=0'));
        $this->assertSame([0], $this->seen);
    }
}
