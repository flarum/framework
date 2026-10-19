<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\AutoloadFiles;
use PHPUnit\Framework\TestCase;

/**
 * Composer requires every `autoload.files` entry on every request, without
 * checking it exists. Removing irmmr/flarum-ext-rtl moved its files entry away
 * mid-run, and every request after that, the updater's own included, died in
 * the autoloader (ClaudiusH, 2026-10-19).
 */
class AutoloadFilesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-autoload-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/vendor/composer', 0775, true);
        mkdir($this->dir.'/vendor/acme/kept', 0775, true);
        mkdir($this->dir.'/src', 0775, true);
        file_put_contents($this->dir.'/vendor/acme/kept/helpers.php', '<?php');
        file_put_contents($this->dir.'/src/root.php', '<?php');

        file_put_contents($this->dir.'/vendor/composer/autoload_static.php', <<<'PHP'
<?php

namespace Composer\Autoload;

class ComposerStaticInitTest
{
    public static $files = array (
        '0123456789abcdef0123456789abcdef' => __DIR__ . '/..' . '/acme/kept/helpers.php',
        'fedcba9876543210fedcba9876543210' => __DIR__ . '/..' . '/irmmr/flarum-ext-rtl/src/Overrides/Frontend/Compiler/LessCompiler.php',
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' => __DIR__ . '/../..' . '/src/root.php',
        'dddddddddddddddddddddddddddddddd' => __DIR__ . '/../..' . '/src/gone.php',
    );

    public static $classMap = array (
        'Irmmr\\Gone' => __DIR__ . '/..' . '/irmmr/flarum-ext-rtl/src/Gone.php',
    );
}

PHP);

        file_put_contents($this->dir.'/vendor/composer/autoload_files.php', <<<'PHP'
<?php

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    '0123456789abcdef0123456789abcdef' => $vendorDir . '/acme/kept/helpers.php',
    'fedcba9876543210fedcba9876543210' => $vendorDir . '/irmmr/flarum-ext-rtl/src/Overrides/Frontend/Compiler/LessCompiler.php',
    'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb' => $baseDir . '/src/root.php',
    'cccccccccccccccccccccccccccccccc' => $baseDir . '/src/gone.php',
);

PHP);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_entries_whose_file_left_are_dropped_and_the_rest_kept(): void
    {
        $pruned = AutoloadFiles::pruneMissing($this->dir.'/vendor');

        $this->assertSame([
            $this->dir.'/vendor/irmmr/flarum-ext-rtl/src/Overrides/Frontend/Compiler/LessCompiler.php',
            $this->dir.'/src/gone.php',
        ], $pruned);

        $static = file_get_contents($this->dir.'/vendor/composer/autoload_static.php');
        $files = file_get_contents($this->dir.'/vendor/composer/autoload_files.php');

        $this->assertStringNotContainsString('Overrides', $static);
        $this->assertStringContainsString('/acme/kept/helpers.php', $static);
        $this->assertStringContainsString("'/src/root.php'", $static);
        $this->assertStringNotContainsString('gone.php', $static);
        // Only `files` entries: a class map line is never required up front.
        $this->assertStringContainsString("'Irmmr\\\\Gone'", $static);
        $this->assertStringNotContainsString('Overrides', $files);
        $this->assertStringNotContainsString('gone.php', $files);
        $this->assertStringContainsString("\$baseDir . '/src/root.php'", $files);
    }

    public function test_the_pruned_autoloader_still_loads(): void
    {
        AutoloadFiles::pruneMissing($this->dir.'/vendor');

        $files = require $this->dir.'/vendor/composer/autoload_files.php';

        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertFileExists($file);
        }
    }

    public function test_nothing_is_written_when_every_file_is_there(): void
    {
        unlink($this->dir.'/vendor/composer/autoload_files.php');
        $static = $this->dir.'/vendor/composer/autoload_static.php';
        file_put_contents($static, str_replace(
            [
                "        'fedcba9876543210fedcba9876543210' => __DIR__ . '/..' . '/irmmr/flarum-ext-rtl/src/Overrides/Frontend/Compiler/LessCompiler.php',\n",
                "        'dddddddddddddddddddddddddddddddd' => __DIR__ . '/../..' . '/src/gone.php',\n",
            ],
            '',
            file_get_contents($static)
        ));
        touch($static, 1000);

        $this->assertSame([], AutoloadFiles::pruneMissing($this->dir.'/vendor'));
        clearstatcache();
        $this->assertSame(1000, filemtime($static));
    }
}
