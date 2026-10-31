<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api;

use Flarum\Api\Resource\UserResource;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

/**
 * The avatar-from-URL fetch (used by OAuth registration) must not be usable to
 * reach link-local / loopback / reserved addresses such as the cloud metadata
 * endpoint (169.254.169.254). Private LAN ranges (RFC1918) MUST stay reachable,
 * otherwise Flarum breaks behind Docker networks, reverse proxies and internal
 * CDNs.
 */
class AvatarUrlIpFilterTest extends TestCase
{
    private function isAllowed(string $ip): bool
    {
        $method = new ReflectionMethod(UserResource::class, 'isAllowedIp');
        $method->setAccessible(true);

        return $method->invoke(null, $ip);
    }

    public static function ipProvider(): array
    {
        return [
            // Blocked: link-local / cloud metadata / loopback / reserved.
            'aws/gcp/azure metadata' => ['169.254.169.254', false],
            'ipv4 loopback' => ['127.0.0.1', false],
            'ipv6 loopback' => ['::1', false],
            'ipv6 link-local' => ['fe80::1', false],
            'unspecified' => ['0.0.0.0', false],

            // Allowed: public + private LAN / Docker must keep working.
            'public ipv4' => ['8.8.8.8', true],
            'docker bridge' => ['172.17.0.2', true],
            'rfc1918 10/8' => ['10.0.0.5', true],
            'rfc1918 192.168/16' => ['192.168.1.10', true],
            'public ipv6' => ['2606:4700:4700::1111', true],
        ];
    }

    #[Test]
    #[DataProvider('ipProvider')]
    public function it_blocks_reserved_ranges_but_allows_private_and_public(string $ip, bool $allowed): void
    {
        $this->assertSame($allowed, $this->isAllowed($ip));
    }
}
