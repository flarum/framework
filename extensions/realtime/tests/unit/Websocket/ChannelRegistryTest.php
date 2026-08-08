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
use Psr\Log\AbstractLogger;
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
        $this->registry->addPresence('acme', fn () => ['displayName' => 'Alice'], scoped: false);

        $this->assertSame(
            ['displayName' => 'Alice'],
            $this->registry->authorizePresence('acme', $this->actor(), null)
        );
    }

    #[Test]
    public function presence_callback_receives_the_channel_id(): void
    {
        $seen = 'unset';

        $this->registry->addPresence('acme', function (User $actor, ?int $id) use (&$seen) {
            $seen = $id;

            return ['ok' => true];
        });

        $this->registry->authorizePresence('acme', $this->actor(), 7);
        $this->assertSame(7, $seen);
    }

    #[Test]
    public function forum_wide_callback_receives_no_id(): void
    {
        $seen = 'unset';

        $this->registry->addPrivate('acme', function (User $actor, ?int $id) use (&$seen) {
            $seen = $id;

            return true;
        }, scoped: false);

        $this->assertTrue($this->registry->authorizePrivate('acme', $this->actor(), null));
        $this->assertNull($seen);
    }

    /**
     * `presence-online` is forum-wide. Before channels declared their shape, the
     * optional id in the presence pattern let a client sign `presence-online=1`,
     * `presence-online=2`, … — as many forum-wide-style rosters as it liked, each
     * publishing the member's display name.
     */
    #[Test]
    public function a_forum_wide_channel_refuses_an_id(): void
    {
        $called = false;
        $this->registry->addPresence('online', function () use (&$called) {
            $called = true;

            return ['displayName' => 'Alice'];
        }, scoped: false);
        $this->registry->addPrivate('feed', fn () => true, scoped: false);

        $this->assertNull($this->registry->authorizePresence('online', $this->actor(), 1));
        $this->assertFalse($this->registry->authorizePrivate('feed', $this->actor(), 1));
        $this->assertFalse($called, 'The callback must not run for a wrongly-shaped name.');

        $this->assertNotNull($this->registry->authorizePresence('online', $this->actor(), null));
        $this->assertTrue($this->registry->authorizePrivate('feed', $this->actor(), null));
    }

    #[Test]
    public function a_scoped_channel_requires_an_id(): void
    {
        $this->registry->addPrivate('acme', fn () => true);
        $this->registry->addPresence('acme', fn () => ['ok' => true]);

        $this->assertFalse($this->registry->authorizePrivate('acme', $this->actor(), null));
        $this->assertNull($this->registry->authorizePresence('acme', $this->actor(), null));
    }

    #[Test]
    public function accepts_reports_whether_a_channel_name_exists(): void
    {
        $this->registry->addPresence('scoped', fn () => ['ok' => true]);
        $this->registry->addPresence('wide', fn () => ['ok' => true], scoped: false);

        $this->assertTrue($this->registry->accepts(ChannelRegistry::PRESENCE, 'scoped', 1));
        $this->assertFalse($this->registry->accepts(ChannelRegistry::PRESENCE, 'scoped', null));
        $this->assertTrue($this->registry->accepts(ChannelRegistry::PRESENCE, 'wide', null));
        $this->assertFalse($this->registry->accepts(ChannelRegistry::PRESENCE, 'wide', 1));
        $this->assertFalse($this->registry->accepts(ChannelRegistry::PRESENCE, 'unregistered', null));
        $this->assertFalse($this->registry->accepts(ChannelRegistry::PRIVATE, 'scoped', 1));
    }

    /**
     * The old controller refused a falsy payload. A presence callback returning `[]`
     * or `true` is far more likely a botched "no" than a deliberate "yes, publish
     * nothing", so neither admits the actor.
     */
    #[Test]
    public function presence_admits_only_a_non_empty_array(): void
    {
        $this->registry->addPresence('empty', fn () => []);
        $this->registry->addPresence('true', fn () => true);
        $this->registry->addPresence('null', fn () => null);

        $this->assertNull($this->registry->authorizePresence('empty', $this->actor(), 1));
        $this->assertNull($this->registry->authorizePresence('true', $this->actor(), 1));
        $this->assertNull($this->registry->authorizePresence('null', $this->actor(), 1));
    }

    #[Test]
    public function presence_channel_refusal_yields_no_member_data(): void
    {
        $this->registry->addPresence('refuses', fn () => false);

        $this->assertNull($this->registry->authorizePresence('refuses', $this->actor(), 1));
        $this->assertNull($this->registry->authorizePresence('unregistered', $this->actor(), 1));
    }

    /**
     * A second registration overwriting the first would give one extension's
     * channel another extension's permissions; throwing would break every channel,
     * since the registry is only built on the first websocket auth request. So the
     * clash fails closed for that subject alone, and is logged.
     */
    #[Test]
    public function a_subject_registered_twice_refuses_everyone_and_is_logged(): void
    {
        $logger = new class extends AbstractLogger {
            public array $errors = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->errors[] = [$level, (string) $message];
            }
        };

        $registry = new ChannelRegistry($logger);
        $registry->addPrivate('acme', fn () => false);
        $registry->addPrivate('acme', fn () => true);
        $registry->addPrivate('other', fn () => true);

        $this->assertFalse($registry->authorizePrivate('acme', $this->actor(), 1));
        $this->assertTrue($registry->authorizePrivate('other', $this->actor(), 1));

        $this->assertCount(1, $logger->errors);
        $this->assertSame('error', $logger->errors[0][0]);
        $this->assertStringContainsString('"acme"', $logger->errors[0][1]);
    }

    #[Test]
    public function private_and_presence_subjects_are_separate_namespaces(): void
    {
        $this->registry->addPrivate('acme', fn () => true);
        $this->registry->addPresence('acme', fn () => ['ok' => true]);

        $this->assertTrue($this->registry->authorizePrivate('acme', $this->actor(), 1));
        $this->assertSame(['ok' => true], $this->registry->authorizePresence('acme', $this->actor(), 1));
    }
}
