<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use PHPUnit\Framework\TestCase;

/**
 * auth.json's temporary file is private from the moment it exists, not from
 * a chmod a moment later: in between, another user could open it, and an open
 * handle keeps reading what is written after.
 */
class JsonFileModeTest extends TestCase
{
    private string $dir;

    private int $umask;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-mode-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
        // The loosest umask a host can have: everything a file is created with shows.
        $this->umask = umask(0);
    }

    protected function tearDown(): void
    {
        umask($this->umask);
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /** A JsonFile that notes each new file's permissions the moment it is created. */
    private function file(string $name, array &$created): JsonFile
    {
        return new class(EnglishTranslator::make(), $this->dir.'/'.$name, $created) extends JsonFile {
            public function __construct($translator, string $path, private array &$created)
            {
                parent::__construct($translator, $path);
            }

            protected function create(string $path, int $mode)
            {
                $handle = parent::create($path, $mode);
                clearstatcache();
                $this->created[basename($path)] = fileperms($path) & 0777;

                return $handle;
            }
        };
    }

    public function test_a_secret_file_is_created_private(): void
    {
        $created = [];

        $this->file('auth.json', $created)->write(['bearer' => ['example.test' => 's3cret']], 0600, false);

        $this->assertNotEmpty($created);
        $this->assertSame([0600], array_values(array_unique($created)));
        $this->assertSame(0600, fileperms($this->dir.'/auth.json') & 0777);
        $this->assertSame(0, umask(), 'the umask is put back');
    }

    public function test_an_ordinary_file_and_its_backup_keep_their_mode(): void
    {
        file_put_contents($this->dir.'/composer.json', '{}');
        chmod($this->dir.'/composer.json', 0664);
        $created = [];

        $this->file('composer.json', $created)->write(['require' => []]);

        $this->assertCount(2, $created, 'the backup and the new file');
        $this->assertSame([0664], array_values(array_unique($created)));
        $this->assertSame(0664, fileperms($this->dir.'/composer.json') & 0777);
        $this->assertSame(0664, fileperms($this->dir.'/composer.json.extension-manager-backup') & 0777);
    }
}
