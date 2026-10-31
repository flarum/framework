<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\Foundation\Config;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use Flarum\OAuthProvider\ClientRegistration\RegistrationRateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetadataDocumentGuardTest extends TestCase
{
    protected function guard(bool $debug = false): object
    {
        return new class(new Config(['url' => 'http://localhost', 'debug' => $debug])) extends MetadataDocumentClients {
            public function __construct(Config $config)
            {
                $this->config = $config;
            }

            public function isPublic(string $ip): bool
            {
                return $this->isPublicAddress($ip);
            }
        };
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function addresses(): array
    {
        return [
            'public IPv4' => ['93.184.216.34', true],
            'public IPv6' => ['2606:2800:220:1:248:1893:25c8:1946', true],
            'private IPv4' => ['10.0.0.5', false],
            'link-local metadata' => ['169.254.169.254', false],
            'CGNAT' => ['100.64.0.1', false],
            'IPv4 loopback' => ['127.0.0.1', false],
            'IPv6 loopback' => ['::1', false],
            'IPv6 ULA' => ['fd00::1', false],
            'IPv4-mapped private' => ['::ffff:10.0.0.1', false],
            'NAT64 well-known, private IPv4' => ['64:ff9b::a00:1', false],
            'NAT64 well-known, public IPv4' => ['64:ff9b::5db8:d822', false],
            'NAT64 well-known, dotted' => ['64:ff9b::169.254.169.254', false],
            'NAT64 local-use' => ['64:ff9b:1::a00:1', false],
            'NAT64 local-use, end of /48' => ['64:ff9b:1:ffff:ffff:ffff:ffff:ffff', false],
            'next to NAT64 local-use' => ['64:ff9b:2::1', true],
            'IPv4-compatible, private IPv4' => ['::a00:1', false],
            'IPv4-compatible, public IPv4' => ['::5db8:d822', false],
            'unspecified' => ['::', false],
        ];
    }

    #[Test]
    #[DataProvider('addresses')]
    public function only_public_addresses_pass(string $ip, bool $public): void
    {
        $this->assertSame($public, $this->guard()->isPublic($ip));
    }

    #[Test]
    public function debug_mode_allows_loopback_only(): void
    {
        $guard = $this->guard(debug: true);

        $this->assertTrue($guard->isPublic('127.0.0.1'));
        $this->assertTrue($guard->isPublic('::1'));
        $this->assertFalse($guard->isPublic('10.0.0.5'));
        $this->assertFalse($guard->isPublic('64:ff9b::7f00:1'));
        $this->assertFalse($guard->isPublic('::a00:1'));
    }

    #[Test]
    public function rate_limit_keys_group_ipv6_by_64(): void
    {
        $this->assertSame('2001:db8:1:2::/64', RegistrationRateLimiter::addressKey('2001:db8:1:2:aaaa:bbbb:cccc:dddd'));
        $this->assertSame('2001:db8:1:2::/64', RegistrationRateLimiter::addressKey('2001:db8:1:2::1'));
        $this->assertNotSame(RegistrationRateLimiter::addressKey('2001:db8:1:2::1'), RegistrationRateLimiter::addressKey('2001:db8:1:3::1'));
        $this->assertSame('203.0.113.7', RegistrationRateLimiter::addressKey('203.0.113.7'));
        $this->assertSame('203.0.113.7', RegistrationRateLimiter::addressKey('::ffff:203.0.113.7'));
    }
}
