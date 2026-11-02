<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

/**
 * Loopback, link-local and reserved addresses (such as the cloud metadata
 * endpoint 169.254.169.254) are blocked. Private LAN ranges stay allowed so
 * Flarum keeps working behind Docker networks, reverse proxies and internal CDNs.
 */
class OutboundAddressResolver
{
    /**
     * The first allowed address of a hostname or (optionally bracketed) IP
     * literal, or null if there is none.
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
