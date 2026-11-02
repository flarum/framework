<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

/**
 * Decides whether Flarum may issue a server-side request to a host.
 *
 * Link-local, loopback and other reserved addresses (e.g. the cloud metadata
 * endpoint 169.254.169.254) are rejected. Private LAN ranges (RFC1918) are
 * intentionally allowed, so Flarum keeps working behind Docker networks,
 * reverse proxies and internal CDNs.
 */
class OutboundAddressResolver
{
    /**
     * Resolve a hostname (or accept a literal IP, bracketed or not) and return
     * the first address that is safe to connect to, or null if the host is
     * unresolvable or only resolves to blocked addresses.
     */
    public function resolve(string $host): ?string
    {
        $literal = trim($host, '[]');

        if (filter_var($literal, FILTER_VALIDATE_IP)) {
            return $this->isAllowedIp($literal) ? $literal : null;
        }

        $ips = array_merge(
            gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')
        );

        foreach ($ips as $ip) {
            if ($this->isAllowedIp($ip)) {
                return $ip;
            }
        }

        return null;
    }

    public function isAllowedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
