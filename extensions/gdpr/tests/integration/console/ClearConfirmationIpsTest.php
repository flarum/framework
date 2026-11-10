<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\console;

use Carbon\Carbon;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ClearConfirmationIpsTest extends ConsoleTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'user_old_ip', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user_old_ip@machine.local', 'is_email_confirmed' => 1],
                ['id' => 3, 'username' => 'user_recent_ip', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user_recent_ip@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'user_no_ip', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user_no_ip@machine.local', 'is_email_confirmed' => 1],
            ],
            'gdpr_erasure' => [
                // Old confirmed request with IP — should be cleared (91 days ago)
                ['id' => 1, 'user_id' => 2, 'verification_token' => null, 'status' => 'user_confirmed', 'created_at' => Carbon::now()->subDays(91), 'user_confirmed_at' => Carbon::now()->subDays(91), 'confirmation_ip' => '1.2.3.4'],
                // Recent confirmed request with IP — should NOT be cleared (1 day ago)
                ['id' => 2, 'user_id' => 3, 'verification_token' => null, 'status' => 'user_confirmed', 'created_at' => Carbon::now()->subDays(1), 'user_confirmed_at' => Carbon::now()->subDays(1), 'confirmation_ip' => '5.6.7.8'],
                // Old confirmed request without IP — unaffected
                ['id' => 3, 'user_id' => 4, 'verification_token' => null, 'status' => 'user_confirmed', 'created_at' => Carbon::now()->subDays(91), 'user_confirmed_at' => Carbon::now()->subDays(91), 'confirmation_ip' => null],
            ],
        ]);
    }

    #[Test]
    public function ips_older_than_90_days_are_cleared()
    {
        $this->runCommand(['command' => 'gdpr:clear-confirmation-ips']);

        $erasureRequest = ErasureRequest::query()->find(1);
        $this->assertNull($erasureRequest->confirmation_ip);
    }

    #[Test]
    public function recent_ips_are_not_cleared()
    {
        $this->runCommand(['command' => 'gdpr:clear-confirmation-ips']);

        $erasureRequest = ErasureRequest::query()->find(2);
        $this->assertEquals('5.6.7.8', $erasureRequest->confirmation_ip);
    }

    #[Test]
    public function records_without_ip_are_unaffected()
    {
        $this->runCommand(['command' => 'gdpr:clear-confirmation-ips']);

        $erasureRequest = ErasureRequest::query()->find(3);
        $this->assertNull($erasureRequest->confirmation_ip);
        // Status should be unchanged
        $this->assertEquals('user_confirmed', $erasureRequest->status);
    }
}
