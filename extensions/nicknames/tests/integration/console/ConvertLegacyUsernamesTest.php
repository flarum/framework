<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration\console;

use Flarum\Audit\AuditLog;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ConvertLegacyUsernamesTest extends ConsoleTestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                // Flarum 1.x gave members who signed up with "Randomize Usernames"
                // an all-digit username, which 2.x no longer accepts.
                ['id' => 2, 'username' => '12345678901234567890', 'nickname' => 'Alice', 'email' => 'alice@machine.local'],
                ['id' => 3, 'username' => '98765', 'nickname' => 'Bob', 'email' => 'bob@machine.local'],
                // An all-digit username with no nickname is what the member is
                // shown as, so it is theirs to keep.
                ['id' => 4, 'username' => '55555555555', 'nickname' => null, 'email' => 'carol@machine.local'],
                ['id' => 5, 'username' => 'user_0a1b2c3d', 'nickname' => 'Dave', 'email' => 'dave@machine.local'],
                ['id' => 6, 'username' => 'erin', 'nickname' => 'Erin E', 'email' => 'erin@machine.local'],
                ['id' => 7, 'username' => '123abc', 'nickname' => 'Frank', 'email' => 'frank@machine.local'],
            ],
        ]);
    }

    protected function convert(array $options = ['--force' => true]): string
    {
        return $this->runCommand(['command' => 'nicknames:convert-legacy-usernames'] + $options);
    }

    protected function username(int $id): string
    {
        return User::query()->findOrFail($id)->username;
    }

    #[Test]
    public function gives_all_digit_usernames_of_members_with_a_nickname_the_current_format()
    {
        $this->convert();

        $this->assertMatchesRegularExpression('/^user_[0-9a-f]{8}$/', $this->username(2));
        $this->assertMatchesRegularExpression('/^user_[0-9a-f]{8}$/', $this->username(3));
        $this->assertNotEquals($this->username(2), $this->username(3));

        $this->assertEquals('Alice', User::query()->findOrFail(2)->nickname);
        $this->assertEquals('Bob', User::query()->findOrFail(3)->nickname);
    }

    #[Test]
    public function leaves_every_other_member_alone()
    {
        $this->convert();

        $this->assertEquals('55555555555', $this->username(4));
        $this->assertEquals('user_0a1b2c3d', $this->username(5));
        $this->assertEquals('erin', $this->username(6));
        $this->assertEquals('123abc', $this->username(7));
    }

    #[Test]
    public function records_each_old_and_new_username_in_the_audit_log()
    {
        $this->convert();

        // Sorted, as nothing fixes the order the members are renamed in.
        $logs = AuditLog::query()->where('action', 'user.username_changed')->get()->sortBy('payload.user_id')->values();

        $this->assertEquals([
            ['user_id' => 2, 'old_username' => '12345678901234567890', 'new_username' => $this->username(2)],
            ['user_id' => 3, 'old_username' => '98765', 'new_username' => $this->username(3)],
        ], $logs->pluck('payload')->all());

        // Renamed from the console: there is no actor or IP address.
        $this->assertEquals([null, null], $logs->pluck('actor_id')->all());
        $this->assertEquals([null, null], $logs->pluck('ip_address')->all());
    }

    #[Test]
    public function a_dry_run_changes_nothing()
    {
        $output = $this->convert(['--dry-run' => true]);

        $this->assertEquals('12345678901234567890', $this->username(2));
        $this->assertEquals('98765', $this->username(3));
        $this->assertStringContainsString('2 members would be renamed', $output);
    }

    #[Test]
    public function does_nothing_unless_confirmed()
    {
        $this->convert(['--no-interaction' => true]);

        $this->assertEquals('12345678901234567890', $this->username(2));
        $this->assertEquals('98765', $this->username(3));
    }

    #[Test]
    public function running_it_again_changes_nothing()
    {
        $this->convert();

        $converted = $this->username(2);

        $output = $this->convert();

        $this->assertEquals($converted, $this->username(2));
        $this->assertStringContainsString('No members have an all-digit username', $output);
    }
}
