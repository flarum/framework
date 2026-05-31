<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\console;

use Carbon\Carbon;
use Flarum\Gdpr\Models\Export;
use Flarum\Notification\Notification;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class DestroyExportsTest extends ConsoleTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'normal', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'normal@machine.local', 'is_email_confirmed' => 1],
            ],
            'gdpr_exports' => [
                // Expired (past destroys_at), ZIP still on disk in test setup below.
                ['id' => 1, 'user_id' => 2, 'actor_id' => 2, 'file' => 'data-export-normal-expired', 'created_at' => Carbon::now()->subDays(2), 'destroys_at' => Carbon::now()->subDay()],
                // Already downloaded — artifact no longer needed even though
                // not yet expired.
                ['id' => 2, 'user_id' => 2, 'actor_id' => 2, 'file' => 'data-export-normal-downloaded', 'created_at' => Carbon::now()->subHour(), 'destroys_at' => Carbon::now()->addHours(23), 'downloaded_at' => Carbon::now()->subMinutes(5), 'downloaded_ip' => '1.2.3.4', 'downloaded_user_agent' => 'TestAgent/1.0'],
                // Fresh, undownloaded — must be left alone.
                ['id' => 3, 'user_id' => 2, 'actor_id' => 2, 'file' => 'data-export-normal-fresh', 'created_at' => Carbon::now(), 'destroys_at' => Carbon::now()->addDay()],
                // Already cleaned up by a prior run — file is null. Ensures the
                // cron is idempotent and doesn't re-process these.
                ['id' => 4, 'user_id' => 2, 'actor_id' => 2, 'file' => null, 'created_at' => Carbon::now()->subWeek(), 'destroys_at' => Carbon::now()->subWeek(), 'downloaded_at' => Carbon::now()->subWeek()],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 2, 'type' => 'gdprExportAvailable', 'subject_id' => 1, 'created_at' => Carbon::now()->subDays(2)],
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 2, 'type' => 'gdprExportAvailable', 'subject_id' => 2, 'created_at' => Carbon::now()->subHour()],
                ['id' => 3, 'user_id' => 2, 'from_user_id' => 2, 'type' => 'gdprExportAvailable', 'subject_id' => 3, 'created_at' => Carbon::now()],
            ],
        ]);

        // Seed storage so the deletion has something to remove for ids 1, 2, 3.
        $fs = $this->getStorageFilesystem();
        foreach ([1, 2, 3] as $id) {
            $fs->put("export-{$id}.zip", 'fake-zip-contents');
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $fs = $this->getStorageFilesystem();
        $fs->delete($fs->allFiles());
    }

    protected function getStorageFilesystem(): Filesystem
    {
        return $this->app()->getContainer()->make(Factory::class)->disk('gdpr-export');
    }

    #[Test]
    public function expired_export_has_zip_deleted_and_file_nulled_but_row_kept()
    {
        $this->runCommand(['command' => 'gdpr:destroy-exports']);

        $export = Export::find(1);
        $this->assertNotNull($export, 'Audit row should be kept for expired export.');
        $this->assertNull($export->file);
        $this->assertFalse($this->getStorageFilesystem()->exists('export-1.zip'));
    }

    #[Test]
    public function downloaded_export_has_zip_deleted_even_before_expiry()
    {
        $this->runCommand(['command' => 'gdpr:destroy-exports']);

        $export = Export::find(2);
        $this->assertNotNull($export);
        $this->assertNull($export->file);
        $this->assertFalse($this->getStorageFilesystem()->exists('export-2.zip'));
        // Audit metadata is preserved.
        $this->assertNotNull($export->downloaded_at);
        $this->assertEquals('1.2.3.4', $export->downloaded_ip);
        $this->assertEquals('TestAgent/1.0', $export->downloaded_user_agent);
    }

    #[Test]
    public function fresh_undownloaded_export_is_untouched()
    {
        $this->runCommand(['command' => 'gdpr:destroy-exports']);

        $export = Export::find(3);
        $this->assertNotNull($export);
        $this->assertEquals('data-export-normal-fresh', $export->file);
        $this->assertTrue($this->getStorageFilesystem()->exists('export-3.zip'));
    }

    #[Test]
    public function already_cleaned_up_row_is_untouched()
    {
        $this->runCommand(['command' => 'gdpr:destroy-exports']);

        $export = Export::find(4);
        $this->assertNotNull($export);
        $this->assertNull($export->file);
    }

    #[Test]
    public function notification_for_cleaned_up_export_is_marked_deleted()
    {
        $this->runCommand(['command' => 'gdpr:destroy-exports']);

        $expired = Notification::find(1);
        $this->assertEquals(1, $expired->is_deleted);

        $downloaded = Notification::find(2);
        $this->assertEquals(1, $downloaded->is_deleted);

        // Fresh export's notification is untouched.
        $fresh = Notification::find(3);
        $this->assertEquals(0, $fresh->is_deleted);
    }
}
