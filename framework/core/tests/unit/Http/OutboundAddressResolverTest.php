<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\OutboundAddressResolver;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Server-side requests (e.g. the avatar-from-URL fetch used by OAuth
 * registration) must not reach link-local / loopback / reserved addresses such
 * as the cloud metadata endpoint (169.254.169.254). Private LAN ranges
 * (RFC1918) MUST stay reachable, otherwise Flarum breaks behind Docker
 * networks, reverse proxies and internal CDNs.
 */
class OutboundAddressResolverTest extends TestCase
{
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
        $this->assertSame($allowed, (new OutboundAddressResolver())->isAllowedIp($ip));
    }

    public static function hostProvider(): array
    {
        return [
            'public ipv4 literal' => ['8.8.8.8', '8.8.8.8'],
            'private ipv4 literal' => ['10.0.0.5', '10.0.0.5'],
            'bracketed public ipv6 literal' => ['[2606:4700:4700::1111]', '2606:4700:4700::1111'],
            'loopback literal' => ['127.0.0.1', null],
            'bracketed ipv6 loopback literal' => ['[::1]', null],
            'metadata literal' => ['169.254.169.254', null],
            'hostname resolving to loopback' => ['localhost', null],
            // RFC 6761: the .invalid TLD never resolves.
            'unresolvable hostname' => ['i-do-not-exist.invalid', null],
        ];
    }

    #[Test]
    #[DataProvider('hostProvider')]
    public function it_resolves_a_host_to_an_allowed_address_or_null(string $host, ?string $expected): void
    {
        $this->assertSame($expected, (new OutboundAddressResolver())->resolve($host));
    }
}
