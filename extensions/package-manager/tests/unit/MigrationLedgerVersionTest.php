<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\MigrationLedger;
use Flarum\Settings\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Undo puts back the version Flarum records through the settings
 * repository, so a cache in front of the settings table hears of it too.
 * Written straight to the table, a cached newer version would leave the
 * restored code showing "Update Flarum" until that cache expired.
 */
class MigrationLedgerVersionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-ledger-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_undo_restores_the_recorded_version_through_the_settings_repository(): void
    {
        file_put_contents($this->dir.'/migrations.before.json', json_encode(['migrations' => [], 'version' => '2.0.1']));

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->expects($this->once())->method('set')->with('version', '2.0.1');

        $pending = (new MigrationLedger(EnglishTranslator::make(), $this->dir))
            ->prepare($settings, $this->createStub(Migrator::class), $this->createStub(ExtensionManager::class), $this->dir);

        $this->assertFalse($pending, 'nothing ran, so nothing waits for the next request');
    }

    public function test_an_unknown_version_is_left_alone(): void
    {
        file_put_contents($this->dir.'/migrations.before.json', json_encode(['migrations' => [], 'version' => null]));

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->expects($this->never())->method('set');

        (new MigrationLedger(EnglishTranslator::make(), $this->dir))
            ->prepare($settings, $this->createStub(Migrator::class), $this->createStub(ExtensionManager::class), $this->dir);
    }
}
