<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\OAuthProvider\Scope\ScopeRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScopeRegistryTest extends TestCase
{
    #[Test]
    public function registered_scope_is_reported_as_present(): void
    {
        $registry = new ScopeRegistry();
        $registry->register('profile', 'Read your profile');

        $this->assertTrue($registry->has('profile'));
        $this->assertSame('Read your profile', $registry->description('profile'));
    }

    #[Test]
    public function unregistered_scope_is_not_present(): void
    {
        $registry = new ScopeRegistry();

        $this->assertFalse($registry->has('unknown'));
        $this->assertNull($registry->description('unknown'));
    }

    #[Test]
    public function all_returns_registered_scopes(): void
    {
        $registry = new ScopeRegistry();
        $registry->register('profile', 'Profile');
        $registry->register('email', 'Email');

        $this->assertSame(
            ['profile' => 'Profile', 'email' => 'Email'],
            $registry->all()
        );
    }

    #[Test]
    public function re_registering_overwrites_description(): void
    {
        $registry = new ScopeRegistry();
        $registry->register('profile', 'Old');
        $registry->register('profile', 'New');

        $this->assertSame('New', $registry->description('profile'));
    }
}
