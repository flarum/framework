<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\Api\PrivateChannelAuthorizer;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrivateChannelAuthorizerTest extends TestCase
{
    private function actor(): User
    {
        return $this->createStub(User::class);
    }

    #[Test]
    public function an_unregistered_subject_is_denied(): void
    {
        $authorizer = new PrivateChannelAuthorizer();

        $this->assertFalse($authorizer->has('postEdit'));
        $this->assertFalse($authorizer->authorize('postEdit', $this->actor(), 1));
    }

    #[Test]
    public function a_guard_returning_true_grants_access_and_receives_the_id(): void
    {
        $authorizer = new PrivateChannelAuthorizer();
        $seen = null;

        $authorizer->add('postEdit', function (User $actor, int $id) use (&$seen) {
            $seen = $id;

            return true;
        });

        $this->assertTrue($authorizer->authorize('postEdit', $this->actor(), 42));
        $this->assertSame(42, $seen);
    }

    public static function nonTrueResults(): array
    {
        return [
            'false' => [false],
            'null' => [null],
            'truthy int' => [1],
            'truthy string' => ['yes'],
        ];
    }

    #[Test]
    #[DataProvider('nonTrueResults')]
    public function anything_but_exactly_true_denies(mixed $result): void
    {
        $authorizer = new PrivateChannelAuthorizer();
        $authorizer->add('postEdit', fn () => $result);

        $this->assertFalse($authorizer->authorize('postEdit', $this->actor(), 1));
    }

    #[Test]
    public function every_guard_must_pass(): void
    {
        $authorizer = new PrivateChannelAuthorizer();
        $authorizer->add('postEdit', fn () => true);
        $authorizer->add('postEdit', fn () => false);

        $this->assertFalse($authorizer->authorize('postEdit', $this->actor(), 1));
    }

    #[Test]
    public function guards_do_not_leak_between_subjects(): void
    {
        $authorizer = new PrivateChannelAuthorizer();
        $authorizer->add('postEdit', fn () => true);

        $this->assertFalse($authorizer->authorize('game', $this->actor(), 1));
    }
}
