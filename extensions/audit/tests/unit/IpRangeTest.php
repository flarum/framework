<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\unit;

use Flarum\Audit\IpRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IpRangeTest extends TestCase
{
    #[Test]
    public function ipv4_is_stored_as_ipv4_mapped_ipv6()
    {
        $this->assertSame('00000000000000000000ffff0a000001', IpRange::normalise('10.0.0.1'));
        $this->assertSame(IpRange::normalise('10.0.0.1'), IpRange::normalise('::ffff:10.0.0.1'));
    }

    #[Test]
    public function ipv6_representations_normalise_to_the_same_value()
    {
        $this->assertSame(
            IpRange::normalise('2a02:390:9e5c:beef:0:0:0:1'),
            IpRange::normalise('2a02:0390:9e5c:BEEF::1')
        );
        $this->assertSame('2a0203909e5cbeef0000000000000001', IpRange::normalise('2a02:390:9e5c:beef::1'));
    }

    #[Test]
    public function values_that_are_not_ip_addresses_do_not_normalise()
    {
        $this->assertNull(IpRange::normalise(null));
        $this->assertNull(IpRange::normalise(''));
        $this->assertNull(IpRange::normalise('not-an-ip'));
        $this->assertNull(IpRange::normalise('10.0.0.256'));
    }

    public static function ranges(): array
    {
        $v4 = '00000000000000000000ffff';

        return [
            'full ipv4 is exact' => ['10.0.0.1', $v4.'0a000001', $v4.'0a000001'],
            'full ipv6 is exact' => ['2a02:390:9e5c:beef::1', '2a0203909e5cbeef0000000000000001', '2a0203909e5cbeef0000000000000001'],
            'ipv4 cidr' => ['192.168.0.0/16', $v4.'c0a80000', $v4.'c0a8ffff'],
            'ipv4 cidr masks host bits' => ['192.168.1.5/24', $v4.'c0a80100', $v4.'c0a801ff'],
            'ipv4 /32' => ['10.0.0.1/32', $v4.'0a000001', $v4.'0a000001'],
            'ipv4 /0 covers all of ipv4 only' => ['0.0.0.0/0', $v4.'00000000', $v4.'ffffffff'],
            'ipv6 /64' => ['2a02:390:9e5c:beef::/64', '2a0203909e5cbeef0000000000000000', '2a0203909e5cbeefffffffffffffffff'],
            'ipv6 /60' => ['2a02:390:9e5c:bee0::/60', '2a0203909e5cbee00000000000000000', '2a0203909e5cbeefffffffffffffffff'],
            'ipv6 /128' => ['::1/128', '00000000000000000000000000000001', '00000000000000000000000000000001'],
            'ipv6 /0' => ['::/0', str_repeat('0', 32), str_repeat('f', 32)],
            'ipv6 prefix not on a byte boundary' => ['2a02:390::/23', '2a020200000000000000000000000000', '2a0203ffffffffffffffffffffffffff'],
            'partial ipv4' => ['192.168', $v4.'c0a80000', $v4.'c0a8ffff'],
            'partial ipv4 with trailing dot' => ['10.', $v4.'0a000000', $v4.'0affffff'],
            'partial ipv4 three octets' => ['10.1.2', $v4.'0a010200', $v4.'0a0102ff'],
            'ipv4 wildcard' => ['192.168.*', $v4.'c0a80000', $v4.'c0a8ffff'],
            'ipv4 wildcard per octet' => ['192.168.*.*', $v4.'c0a80000', $v4.'c0a8ffff'],
            'partial ipv6 is a whole-group prefix' => ['2a02:390:9e5c:beef', '2a0203909e5cbeef0000000000000000', '2a0203909e5cbeefffffffffffffffff'],
            'partial ipv6 with trailing colon' => ['2a02:', '2a020000000000000000000000000000', '2a02ffffffffffffffffffffffffffff'],
            'ipv6 wildcard' => ['2a02:390:*', '2a020390000000000000000000000000', '2a020390ffffffffffffffffffffffff'],
            'surrounding whitespace is ignored' => [' 10.0.0.1 ', $v4.'0a000001', $v4.'0a000001'],
        ];
    }

    #[Test]
    #[DataProvider('ranges')]
    public function input_parses_to_its_range(string $input, string $low, string $high)
    {
        $this->assertSame([$low, $high], IpRange::parse($input));
    }

    public static function notRanges(): array
    {
        return [
            'empty' => [''],
            'text' => ['localhost'],
            'bare number is ambiguous between ipv4 and ipv6' => ['10'],
            'octet out of range' => ['192.300'],
            'ipv4 prefix too long' => ['10.0.0.0/33'],
            'ipv6 prefix too long' => ['::/129'],
            'prefix not a number' => ['10.0.0.0/abc'],
            'negative prefix' => ['10.0.0.0/-1'],
            'empty prefix' => ['10.0.0.0/'],
            'cidr on a partial address' => ['10.0/16'],
            'partial ipv6 with :: is ambiguous' => ['2a02::beef:'],
            'too many ipv6 groups for a partial' => ['1:2:3:4:5:6:7:8:9'],
            'ipv6 group too long' => ['2a020:390'],
            'wildcard in the middle' => ['10.*.0.1'],
        ];
    }

    #[Test]
    #[DataProvider('notRanges')]
    public function input_that_is_not_an_address_or_range_is_rejected(string $input)
    {
        $this->assertNull(IpRange::parse($input));
    }
}
