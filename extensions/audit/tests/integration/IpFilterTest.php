<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Audit\IpRange;
use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The `ip:` filter: exact addresses, partial addresses and subnets, for IPv4 and IPv6.
 */
class IpFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->setting('flarum-audit.limitedIpAddress', '0');
        $this->setting('flarum-audit.limitedActions', '');

        $row = fn (int $id, ?string $ip, bool $indexed = true) => [
            'id' => $id,
            'actor_id' => 1,
            'client' => $ip === null ? 'cli' : 'session',
            'ip_address' => $ip,
            'ip_hex' => $indexed ? IpRange::normalise($ip) : null,
            'action' => 'cache_cleared',
            'payload' => null,
            'created_at' => '2022-01-01 12:00:'.str_pad((string) $id, 2, '0', STR_PAD_LEFT),
        ];

        $this->prepareDatabase([
            'audit_log' => [
                $row(1, '10.0.0.1'),
                $row(2, '10.0.5.7'),
                $row(3, '192.168.1.20'),
                // Stored compressed: a text prefix of `2a02:390:9e5c:beef:` would miss it.
                $row(4, '2a02:390:9e5c:beef::1'),
                $row(5, '2a02:390:9e5c:beef:1:2:3:4'),
                // Inside 2a02:390:9e5c:bee0::/60 but outside the /64 of rows 4 and 5.
                $row(6, '2a02:390:9e5c:bee1::1'),
                $row(7, '2a02:390:9e5c:bf00::1'),
                // Logged before ip_hex existed, and not backfilled yet.
                $row(8, '10.0.0.9', false),
                // No IP address at all, e.g. a console action.
                $row(9, null),
            ],
            User::class => [
                $this->normalUser(),
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Auditor', 'name_plural' => 'Auditors'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 5],
            ],
            Permission::class => [
                ['group_id' => 5, 'permission' => 'flarum-audit.viewLimited'],
            ],
        ]);
    }

    /**
     * @return string[] The ids of the entries the filter returns.
     */
    protected function search(array $filter, int $actorId = 1): array
    {
        $response = $this->send(
            $this->request('GET', '/api/audit', ['authenticatedAs' => $actorId])
                ->withQueryParams(['filter' => $filter, 'page' => ['limit' => 50]])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Assert request status code');

        return Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id');
    }

    public static function searches(): array
    {
        return [
            'exact ipv4' => ['10.0.0.1', ['1']],
            'exact ipv4 not yet backfilled' => ['10.0.0.9', ['8']],
            'exact ipv6 in another representation' => ['2a02:390:9e5c:beef:0:0:0:1', ['4']],
            'partial ipv4' => ['10.0', ['1', '2']],
            'ipv4 wildcard' => ['192.168.*', ['3']],
            'ipv4 cidr' => ['10.0.0.0/24', ['1']],
            'partial ipv6 is its /64' => ['2a02:390:9e5c:beef', ['4', '5']],
            'ipv6 cidr /64' => ['2a02:390:9e5c:beef::/64', ['4', '5']],
            'ipv6 cidr /60' => ['2a02:390:9e5c:bee0::/60', ['4', '5', '6']],
            'several values match any of them' => ['10.0.0.1,192.168.0.0/16, 2a02:390:9e5c:bf00::1', ['1', '3', '7']],
            'ipv4 range does not match ipv6' => ['0.0.0.0/0', ['1', '2', '3']],
            'not an address matches only that exact text' => ['localhost', []],
        ];
    }

    #[Test]
    #[DataProvider('searches')]
    public function ip_filter_matches_addresses_and_ranges(string $ip, array $expected)
    {
        $this->assertEqualsCanonicalizing($expected, $this->search(['ip' => $ip]));
    }

    #[Test]
    public function negated_exact_ip_keeps_entries_without_an_ip_address()
    {
        $this->assertEqualsCanonicalizing(
            ['2', '3', '4', '5', '6', '7', '8', '9'],
            $this->search(['-ip' => '10.0.0.1'])
        );
    }

    #[Test]
    public function negated_range_excludes_the_range()
    {
        // 8 is in 10.0.0.0/8 but not backfilled yet, so a range can't see it.
        $this->assertEqualsCanonicalizing(
            ['3', '4', '5', '6', '7', '8', '9'],
            $this->search(['-ip' => '10.0.0.0/8'])
        );
    }

    #[Test]
    public function new_entries_are_found_by_range()
    {
        $this->app();

        AuditLogger::$ipAddress = '2a02:390:9e5c:beef:abcd::5';

        try {
            AuditLogger::log('cache_cleared');
        } finally {
            AuditLogger::$ipAddress = null;
        }

        $log = AuditLog::query()->where('ip_address', '2a02:390:9e5c:beef:abcd::5')->firstOrFail();

        $this->assertSame('2a0203909e5cbeefabcd000000000005', $log->ip_hex);
        $this->assertContains((string) $log->id, $this->search(['ip' => '2a02:390:9e5c:beef']));
    }

    #[Test]
    public function limited_actor_cannot_search_by_range_without_the_ip_setting()
    {
        $this->assertCount(9, $this->search(['ip' => '10.0.0.0/8'], 2));
    }

    #[Test]
    public function limited_actor_can_search_by_range_with_the_ip_setting()
    {
        $this->setting('flarum-audit.limitedIpAddress', '1');

        $this->assertEqualsCanonicalizing(['1', '2'], $this->search(['ip' => '10.0.0.0/8'], 2));
    }

    #[Test]
    public function logging_still_works_after_the_ip_hex_migration_is_rolled_back()
    {
        // Uninstalling rolls the migrations back, then logs extension.uninstalled.
        $schema = $this->app()->getContainer()->make('db')->getSchemaBuilder();
        (include __DIR__.'/../../migrations/2026_11_08_000000_add_audit_log_ip_hex_column.php')['down']($schema);

        AuditLogger::$ipAddress = '10.9.9.9';

        try {
            AuditLogger::log('extension.uninstalled');
        } finally {
            AuditLogger::$ipAddress = null;
        }

        $this->assertSame(1, AuditLog::query()->where('ip_address', '10.9.9.9')->count());
    }
}
