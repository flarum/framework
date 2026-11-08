<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

/**
 * Turns IP addresses into a form that can be range-searched in SQL.
 *
 * The audit log keeps `ip_address` verbatim, and the same IPv6 address can be
 * written many ways (`2a02:390:9e5c:beef:0:0:0:1` and `2a02:390:9e5c:beef::1`
 * are one address). Where `::` falls depends on the zeros, not on the subnet
 * boundary, so no text prefix can match an IPv6 subnet reliably.
 *
 * Every address is therefore also stored as the 32 lowercase hex digits of its
 * 16-byte form, with IPv4 as IPv4-mapped IPv6 (`::ffff:a.b.c.d`) so both
 * families share one ordering. Fixed-length lowercase hex sorts the same as
 * the bytes under every driver and collation, so a subnet is a plain indexed
 * `BETWEEN low AND high`. Hex rather than a binary column, because binary
 * strings need special parameter binding for PostgreSQL's `bytea` and SQLite's
 * blobs.
 */
class IpRange
{
    /**
     * Prefix of an IPv4-mapped IPv6 address: 80 zero bits, then 16 one bits.
     */
    private const IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /**
     * The 32-digit hex form of an IP address, or null if it isn't one.
     */
    public static function normalise(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        $packed = self::pack(trim($ip));

        return $packed === null ? null : bin2hex($packed);
    }

    /**
     * The inclusive [low, high] hex bounds matched by a search input, or null
     * if the input is neither an address nor a range.
     *
     * Accepted: a full address (an exact match), CIDR (`10.0.0.0/8`,
     * `2a02:390:9e5c:beef::/64`) and a partial address of whole leading parts,
     * optionally ending in wildcards (`192.168`, `192.168.*`, `10.`,
     * `2a02:390:9e5c:beef`, `2a02:390:*`). A partial needs at least one `.` or
     * `:`, as a bare `10` could be either family.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parse(string $input): ?array
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if (str_contains($input, '/')) {
            return self::parseCidr($input);
        }

        if (($packed = self::pack($input)) !== null) {
            $hex = bin2hex($packed);

            return [$hex, $hex];
        }

        return self::parsePartialIpv4($input) ?? self::parsePartialIpv6($input);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function parseCidr(string $input): ?array
    {
        [$address, $prefix] = explode('/', $input, 2);

        if (! ctype_digit($prefix)) {
            return null;
        }

        $raw = @inet_pton($address);

        if ($raw === false) {
            return null;
        }

        $prefix = (int) $prefix;
        $isIpv4 = strlen($raw) === 4;

        if ($prefix > ($isIpv4 ? 32 : 128)) {
            return null;
        }

        return self::range(self::pack($address), $isIpv4 ? $prefix + 96 : $prefix);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function parsePartialIpv4(string $input): ?array
    {
        if (! preg_match('/^\d{1,3}(?:\.\d{1,3}){0,2}(?:\.\*)*\.?$/', $input) || ! str_contains($input, '.')) {
            return null;
        }

        $octets = array_values(array_filter(explode('.', $input), fn (string $part) => $part !== '' && $part !== '*'));

        foreach ($octets as $octet) {
            if ((int) $octet > 255) {
                return null;
            }
        }

        $address = implode('.', array_pad($octets, 4, '0'));

        return self::range(self::pack($address), 96 + 8 * count($octets));
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function parsePartialIpv6(string $input): ?array
    {
        if (! preg_match('/^[0-9a-f]{1,4}(?::[0-9a-f]{1,4}){0,6}(?::\*)*:?$/i', $input) || ! str_contains($input, ':')) {
            return null;
        }

        $groups = array_values(array_filter(explode(':', $input), fn (string $part) => $part !== '' && $part !== '*'));

        $address = implode(':', array_pad($groups, 8, '0'));

        return self::range(self::pack($address), 16 * count($groups));
    }

    /**
     * The 16-byte form of an address, IPv4 as IPv4-mapped IPv6.
     */
    private static function pack(string $ip): ?string
    {
        $raw = @inet_pton($ip);

        if ($raw === false) {
            return null;
        }

        return strlen($raw) === 4 ? self::IPV4_MAPPED_PREFIX.$raw : $raw;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function range(?string $packed, int $prefix): ?array
    {
        if ($packed === null) {
            return null;
        }

        $low = '';
        $high = '';

        for ($i = 0; $i < 16; $i++) {
            $bits = max(0, min(8, $prefix - 8 * $i));
            $mask = (0xFF << (8 - $bits)) & 0xFF;
            $byte = ord($packed[$i]);

            $low .= chr($byte & $mask);
            $high .= chr($byte | (~$mask & 0xFF));
        }

        return [bin2hex($low), bin2hex($high)];
    }
}
