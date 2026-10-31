<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\LocaleManager;
use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LocaleManagerClearCacheTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/flarum-locale-clear-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $entry) {
            is_dir($entry) ? rmdir($entry) : unlink($entry);
        }

        rmdir($this->dir);

        parent::tearDown();
    }

    #[Test]
    public function an_entry_that_cannot_be_deleted_does_not_stop_the_clear()
    {
        // Another process can delete a listed file before the sweep reaches it
        // (a concurrent clear, or a writer renaming its temporary file). A
        // directory fails unlink() the same way, deterministically.
        mkdir($this->dir.'/a-entry-that-cannot-be-deleted');
        touch($this->dir.'/catalogue.en.php');

        // Report every warning, and escalate it as some error handlers do.
        $reporting = error_reporting(E_ALL);
        set_error_handler(function (int $level, string $message, string $file, int $line) {
            if (error_reporting() & $level) {
                throw new \ErrorException($message, 0, $level, $file, $line);
            }

            return true;
        });

        try {
            (new LocaleManager(new Translator('en'), $this->dir))->clearCache();
        } finally {
            restore_error_handler();
            error_reporting($reporting);
        }

        $this->assertFileDoesNotExist($this->dir.'/catalogue.en.php');
    }
}
