<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\Test;

class BackfillIpCommandTest extends ConsoleTestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();
        $this->extension('flarum-audit');

        $row = fn (int $id, ?string $ip, ?string $hex = null) => [
            'id' => $id,
            'actor_id' => 1,
            'client' => 'session',
            'ip_address' => $ip,
            'ip_hex' => $hex,
            'action' => 'cache_cleared',
            'payload' => null,
            'created_at' => '2022-01-01 12:00:00',
        ];

        $this->prepareDatabase([
            'audit_log' => [
                $row(1, '10.0.0.1'),
                $row(2, '10.0.0.1'),
                $row(3, '2a02:390:9e5c:beef::1'),
                $row(4, 'not-an-ip'),
                $row(5, null),
                // Already indexed: left as it is.
                $row(6, '10.0.0.2', '00000000000000000000ffff0a000002'),
            ],
        ]);
    }

    /**
     * @return array<int, string|null> ip_hex by entry id.
     */
    protected function ipHexById(): array
    {
        return AuditLog::query()->orderBy('id')->pluck('ip_hex', 'id')->all();
    }

    #[Test]
    public function it_indexes_entries_logged_before_ip_hex_existed()
    {
        $output = $this->runCommand(['command' => 'audit:backfill-ip', '--chunk' => 2]);

        $this->assertSame([
            1 => '00000000000000000000ffff0a000001',
            2 => '00000000000000000000ffff0a000001',
            3 => '2a0203909e5cbeef0000000000000001',
            4 => null,
            5 => null,
            6 => '00000000000000000000ffff0a000002',
        ], $this->ipHexById());

        $this->assertStringContainsString('3 entries indexed.', $output);
        $this->assertStringContainsString('1 entries have an IP address value', $output);
    }

    #[Test]
    public function running_it_again_changes_nothing()
    {
        $this->runCommand(['command' => 'audit:backfill-ip']);
        $before = $this->ipHexById();

        $output = $this->runCommand(['command' => 'audit:backfill-ip']);

        $this->assertSame($before, $this->ipHexById());
        $this->assertStringContainsString('Nothing left to index.', $output);
    }

    #[Test]
    public function it_rejects_a_chunk_size_below_one()
    {
        $output = $this->runCommand(['command' => 'audit:backfill-ip', '--chunk' => 0]);

        $this->assertStringContainsString('--chunk must be a positive number.', $output);
        $this->assertNull($this->ipHexById()[1]);
    }
}
