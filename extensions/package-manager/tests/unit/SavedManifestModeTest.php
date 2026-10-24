<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerSteps;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * composer.json can hold http-basic credentials or a token in a repository
 * URL, so the copy a run keeps of it, and the file put back from that copy,
 * must be no more readable than the original.
 */
class SavedManifestModeTest extends TestCase
{
    private string $dir;

    private int $umask;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-mode-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/work', 0775, true);
        file_put_contents($this->dir.'/composer.json', '{"repositories":[{"type":"composer","url":"https://user:secret@repo.example.test"}]}');
        file_put_contents($this->dir.'/composer.lock', '{"packages":[]}');
        chmod($this->dir.'/composer.json', 0600);
        chmod($this->dir.'/composer.lock', 0640);
        // The usual web server umask, which leaves a plain copy() world-readable.
        $this->umask = umask(022);
    }

    protected function tearDown(): void
    {
        umask($this->umask);
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function startFromSaved(): void
    {
        $reflection = new ReflectionClass(ComposerSteps::class);
        $steps = $reflection->newInstanceWithoutConstructor();

        foreach (['installPath' => $this->dir, 'workDir' => $this->dir.'/work', 'translator' => EnglishTranslator::make()] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($steps, $value);
        }

        $reflection->getMethod('startFromSaved')->invoke($steps);
    }

    private function mode(string $file): int
    {
        clearstatcache();

        return fileperms($file) & 0777;
    }

    public function test_the_saved_copies_keep_the_originals_mode(): void
    {
        $this->startFromSaved();

        $this->assertSame(0600, $this->mode($this->dir.'/work/composer.json.before'));
        $this->assertSame(0640, $this->mode($this->dir.'/work/composer.lock.before'));
        $this->assertFileEquals($this->dir.'/composer.json', $this->dir.'/work/composer.json.before');
        $this->assertSame([], glob($this->dir.'/work/*.extension-manager-*') ?: [], 'no temporary file left behind');
    }

    public function test_a_retry_puts_the_saved_copy_back_without_changing_the_live_files_mode(): void
    {
        $this->startFromSaved();
        $original = (string) file_get_contents($this->dir.'/composer.json');
        file_put_contents($this->dir.'/composer.json', '{"changed":true}');

        $this->startFromSaved();

        $this->assertSame($original, file_get_contents($this->dir.'/composer.json'));
        $this->assertSame(0600, $this->mode($this->dir.'/composer.json'));
        $this->assertSame([], glob($this->dir.'/*.extension-manager-*') ?: [], 'no temporary file left behind');
    }

    public function test_a_read_only_original_is_still_saved_with_its_mode(): void
    {
        chmod($this->dir.'/composer.json', 0444);
        chmod($this->dir.'/composer.lock', 0400);

        $this->startFromSaved();

        $this->assertSame(0444, $this->mode($this->dir.'/work/composer.json.before'));
        $this->assertSame(0400, $this->mode($this->dir.'/work/composer.lock.before'));
        $this->assertFileEquals($this->dir.'/composer.lock', $this->dir.'/work/composer.lock.before');
    }
}
