<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\Api\ChannelRegistry;
use Flarum\User\User;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The registry decides who may subscribe to a channel, so it has to fail closed:
 * an unregistered subject, a callback that refuses, and a callback that returns
 * something other than a clear "yes" must all be refusals.
 *
 * The complementary integration test (ExtensionChannelAuthTest) drives the same
 * rules through the HTTP endpoint with real permissions.
 */
class ChannelRegistryTest extends TestCase
{
    private ChannelRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ChannelRegistry;
    }

    private function actor(): User
    {
        return new User;
    }

    #[Test]
    public function registered_private_channel_is_authorized_by_its_callback(): void
    {
        $this->registry->addPrivate('acme', fn (User $actor, int $id) => $id === 1);

        $this->assertTrue($this->registry->authorizePrivate('acme', $this->actor(), 1));
        $this->assertFalse($this->registry->authorizePrivate('acme', $this->actor(), 2));
    }

    #[Test]
    public function private_callback_receives_the_channel_id(): void
    {
        $seen = null;

        $this->registry->addPrivate('acme', function (User $actor, int $id) use (&$seen) {
            $seen = $id;

            return true;
        });

        $this->registry->authorizePrivate('acme', $this->actor(), 42);

        $this->assertSame(42, $seen);
    }

    #[Test]
    public function unregistered_private_subject_is_refused(): void
    {
        $this->assertFalse($this->registry->authorizePrivate('nope', $this->actor(), 1));
    }

    /**
     * Guards against a callback whose truthy-but-not-true return (a model, a
     * non-empty string) is mistaken for permission.
     */
    #[Test]
    public function private_callback_must_return_exactly_true(): void
    {
        $this->registry->addPrivate('truthy', fn () => 'yes');
        $this->registry->addPrivate('nullish', fn () => null);

        $this->assertFalse($this->registry->authorizePrivate('truthy', $this->actor(), 1));
        $this->assertFalse($this->registry->authorizePrivate('nullish', $this->actor(), 1));
    }

    #[Test]
    public function registered_presence_channel_returns_its_member_data(): void
    {
        $this->registry->addPresence('acme', fn () => ['displayName' => 'Alice']);

        $this->assertSame(
            ['displayName' => 'Alice'],
            $this->registry->authorizePresence('acme', $this->actor(), null)
        );
    }

    #[Test]
    public function presence_callback_receives_the_optional_channel_id(): void
    {
        $seen = 'unset';

        $this->registry->addPresence('acme', function (User $actor, ?int $id) use (&$seen) {
            $seen = $id;

            return [];
        });

        $this->registry->authorizePresence('acme', $this->actor(), 7);
        $this->assertSame(7, $seen);

        $this->registry->authorizePresence('acme', $this->actor(), null);
        $this->assertNull($seen);
    }

    #[Test]
    public function presence_channel_refusal_yields_no_member_data(): void
    {
        $this->registry->addPresence('refuses', fn () => false);

        $this->assertNull($this->registry->authorizePresence('refuses', $this->actor(), null));
        $this->assertNull($this->registry->authorizePresence('unregistered', $this->actor(), null));
    }

    /**
     * A second registration silently overwriting the first would give one
     * extension's channel another extension's permissions.
     */
    #[Test]
    public function a_subject_cannot_be_registered_twice(): void
    {
        $this->registry->addPrivate('acme', fn () => true);

        $this->expectException(InvalidArgumentException::class);

        $this->registry->addPrivate('acme', fn () => true);
    }

    #[Test]
    public function private_and_presence_subjects_are_separate_namespaces(): void
    {
        $this->registry->addPrivate('acme', fn () => true);
        $this->registry->addPresence('acme', fn () => ['ok' => true]);

        $this->assertTrue($this->registry->authorizePrivate('acme', $this->actor(), 1));
        $this->assertSame(['ok' => true], $this->registry->authorizePresence('acme', $this->actor(), null));
    }
}
