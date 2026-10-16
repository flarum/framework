<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\InstalledRecord;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule these protect: after an update is applied, the register step's
 * `composer install` must have NOTHING to do.
 *
 * When it had something to do it re-extracted every changed package, using a
 * Composer that lives in the same vendor/ — and on wowcraft.online, 2026-10-02,
 * it deleted symfony/finder out from under itself and left 33 packages empty.
 * The last test here runs real Composer to prove the record sync is what makes
 * it a no-op.
 */
class InstalledRecordTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-record-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/vendor/composer', 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_the_record_names_exactly_what_the_lock_names(): void
    {
        $this->json('composer.lock', [
            'packages' => [
                ['name' => 'acme/widget', 'version' => '2.0.0', 'dist' => ['type' => 'zip', 'url' => 'x', 'reference' => 'b']],
                ['name' => 'acme/fresh', 'version' => '1.0.0', 'source' => ['type' => 'git', 'url' => 'y', 'reference' => 'c']],
            ],
            'packages-dev' => [
                ['name' => 'acme/tool', 'version' => '3.0.0', 'dist' => ['type' => 'zip', 'url' => 'z', 'reference' => 'd']],
            ],
        ]);
        $this->json('vendor/composer/installed.json', [
            'packages' => [
                ['name' => 'acme/widget', 'version' => '1.0.0', 'version_normalized' => '1.0.0.0', 'install-path' => '../../custom/widget'],
                ['name' => 'acme/gone', 'version' => '1.0.0', 'install-path' => '../acme/gone'],
            ],
            'dev' => true,
            'dev-package-names' => [],
        ]);

        $count = (new InstalledRecord($this->dir))->syncFromLock();

        $record = json_decode(file_get_contents($this->dir.'/vendor/composer/installed.json'), true);
        $byName = array_column($record['packages'], null, 'name');

        $this->assertSame(3, $count);
        $this->assertSame(['acme/widget', 'acme/fresh', 'acme/tool'], array_keys($byName), 'removed packages leave the record');
        $this->assertSame('2.0.0', $byName['acme/widget']['version']);
        $this->assertArrayNotHasKey('version_normalized', $byName['acme/widget'], 'a stale normalised version would describe the OLD release');
        $this->assertSame('../../custom/widget', $byName['acme/widget']['install-path'], 'a custom install path survives');
        $this->assertSame('../acme/fresh', $byName['acme/fresh']['install-path']);
        $this->assertSame('dist', $byName['acme/widget']['installation-source']);
        $this->assertSame('source', $byName['acme/fresh']['installation-source']);
        $this->assertSame(['acme/tool'], $record['dev-package-names']);
        $this->assertFileDoesNotExist($this->dir.'/vendor/composer/installed.json.extension-manager');
    }

    public function test_the_dry_run_count_is_read_from_composers_own_summary(): void
    {
        $this->assertSame(33, InstalledRecord::plannedOperations("Package operations: 0 installs, 33 updates, 0 removals\n"));
        $this->assertSame(3, InstalledRecord::plannedOperations('Package operations: 1 install, 1 update, 1 removal'));
        $this->assertSame(0, InstalledRecord::plannedOperations("Nothing to install, update or remove\n"));
    }

    public function test_real_composer_has_nothing_left_to_extract_once_the_record_is_synced(): void
    {
        $composer = dirname((new \ReflectionClass(\Composer\Composer::class))->getFileName(), 3).'/bin/composer';
        if (! is_file($composer)) {
            $this->markTestSkipped('Composer is not installed in this checkout.');
        }

        // A package at 1.0.0, installed by Composer for real, from a path
        // repository so the test needs no network.
        $pkg = $this->dir.'/src/widget';
        mkdir($pkg, 0775, true);
        $this->writePackage($pkg, '1.0.0');
        $this->json('composer.json', [
            'name' => 'acme/site',
            'repositories' => [
                ['packagist.org' => false],
                ['type' => 'path', 'url' => $pkg, 'options' => ['symlink' => false]],
            ],
            'require' => ['acme/widget' => '*'],
        ]);
        $runner = new ComposerRunner($this->dir, $composer, $this->dir.'/.composer');
        $this->assertSame(0, $runner->run(['install', '--no-scripts'])['code']);

        // What the Extension Manager does: plan to 2.0.0 without installing, then place
        // the new files itself. Disk and lock now say 2.0.0; the record says 1.0.0.
        $this->writePackage($pkg, '2.0.0');
        $this->assertSame(0, $runner->run(['update', '--no-install', '--no-scripts'])['code']);
        copy($pkg.'/composer.json', $this->dir.'/vendor/acme/widget/composer.json');

        $before = $runner->run(['install', '--no-scripts', '--dry-run']);
        $this->assertSame(1, InstalledRecord::plannedOperations($before['output']), 'the stale record is what makes Composer re-extract');

        (new InstalledRecord($this->dir))->syncFromLock();

        $after = $runner->run(['install', '--no-scripts', '--dry-run']);
        $this->assertSame(0, $after['code'], $after['output']);
        $this->assertSame(0, InstalledRecord::plannedOperations($after['output']), $after['output']);

        // And the real install, which is what the register step runs, still
        // produces a working autoloader that knows the new version.
        $this->assertSame(0, $runner->run(['install', '--no-scripts'])['code']);
        $installed = require $this->dir.'/vendor/composer/installed.php';
        $this->assertSame('2.0.0', $installed['versions']['acme/widget']['pretty_version']);
    }

    private function writePackage(string $dir, string $version): void
    {
        file_put_contents($dir.'/composer.json', json_encode([
            'name' => 'acme/widget',
            'version' => $version,
            'autoload' => ['psr-4' => ['Acme\\Widget\\' => '']],
        ]));
    }

    private function json(string $path, array $data): void
    {
        file_put_contents($this->dir.'/'.$path, json_encode($data));
    }
}
