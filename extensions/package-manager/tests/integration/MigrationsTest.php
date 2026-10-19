<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration;

use Flarum\Database\Migrator;
use Flarum\Extension\Extension;
use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Extension Manager's migrations, run through core's Migrator, from every
 * state an existing forum can be in: a fresh install, a forum whose rows are
 * still under the old `flarum-package-manager` ID, and one that ran the 2024
 * rename. Each one is then rolled back as a purge would, and must leave nothing.
 */
class MigrationsTest extends TestCase
{
    protected const ID = 'flarum-extension-manager';
    protected const OLD_ID = 'flarum-package-manager';

    protected const CREATE = '2022_02_22_000000_create_package_manager_tasks_table';
    protected const ADD_CAUSE = '2023_12_09_000000_add_guessed_cause_column_to_package_manager_tasks_table';
    protected const RENAME = '2024_01_10_000000_rename_to_extension_manager';
    protected const DROP = '2026_10_17_000000_drop_extension_manager_tasks_table';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app();
        $this->clean();
    }

    protected function tearDown(): void
    {
        $this->clean();

        // DDL implicitly commits the transaction the harness opened on MySQL and
        // MariaDB. Reconnect to reset Laravel's transaction counter, then open a
        // fresh transaction for the harness's tearDown to roll back.
        $db = $this->app()->getContainer()->make('db');
        $db->connection()->disconnect();
        $db->connection()->reconnect();
        $db->connection()->beginTransaction();

        parent::tearDown();
    }

    #[Test]
    public function a_fresh_install_migrates_up_and_back_down(): void
    {
        $this->migrator()->run($this->path(), $this->manager());

        $this->assertNoTables();
        $this->assertSame([self::CREATE, self::ADD_CAUSE, self::RENAME, self::DROP], $this->ran(self::ID));
        $this->assertSame([], $this->ran(self::OLD_ID));

        $this->rollBack();
    }

    #[Test]
    public function it_takes_over_from_a_forum_still_under_the_package_manager_id(): void
    {
        $this->migrateAs(self::OLD_ID, [self::CREATE, self::ADD_CAUSE]);
        $this->database()->table('package_manager_tasks')->insert($this->task());
        $this->oldSettings(self::OLD_ID);

        $this->migrator()->run($this->path(), $this->manager());

        $this->assertNoTables();
        $this->assertSame([self::CREATE, self::ADD_CAUSE, self::RENAME, self::DROP], $this->ran(self::ID));
        $this->assertSame([], $this->ran(self::OLD_ID), 'the 2024 rename clears the old ID’s rows');
        $this->assertNoOldSettings();

        $this->rollBack();
    }

    #[Test]
    public function it_takes_over_from_a_forum_that_ran_the_2024_rename(): void
    {
        $this->migrateAs(self::ID, [self::CREATE, self::ADD_CAUSE, self::RENAME]);
        $this->database()->table('extension_manager_tasks')->insert($this->task());
        $this->oldSettings(self::OLD_ID);
        $this->oldSettings(self::ID);

        $this->migrator()->run($this->path(), $this->manager());

        $this->assertNoTables();
        $this->assertSame([self::CREATE, self::ADD_CAUSE, self::RENAME, self::DROP], $this->ran(self::ID));
        $this->assertNoOldSettings();

        $this->rollBack();
    }

    /**
     * Purged or rolled back before `migrate` ran the new migration: the earlier
     * migrations' own down()s must still complete.
     */
    #[Test]
    public function a_forum_that_never_ran_the_new_migration_still_rolls_back_cleanly(): void
    {
        $this->migrateAs(self::ID, [self::CREATE, self::ADD_CAUSE, self::RENAME]);
        $this->database()->table('extension_manager_tasks')->insert($this->task());

        $this->rollBack();
    }

    protected function rollBack(): void
    {
        $this->migrator()->reset($this->path(), $this->manager());

        $this->assertNoTables();
        $this->assertSame([], $this->ran(self::ID), 'no migration rows are left under the extension’s ID');
        $this->assertSame([], $this->ran(self::OLD_ID), 'and none are relabelled to the old one');
    }

    /**
     * Run some of the migrations as an older forum would have, under either ID.
     *
     * @param list<string> $migrations
     */
    protected function migrateAs(string $id, array $migrations): void
    {
        foreach ($migrations as $migration) {
            (include $this->path().'/'.$migration.'.php')['up']($this->schema());

            $this->database()->table('migrations')->insert(['migration' => $migration, 'extension' => $id]);
        }
    }

    protected function oldSettings(string $id): void
    {
        $this->database()->table('settings')->insert([
            ['key' => $id.'.queue_jobs', 'value' => '1'],
            ['key' => $id.'.last_update_check', 'value' => '{}'],
        ]);
    }

    protected function assertNoOldSettings(): void
    {
        $left = $this->database()->table('settings')
            ->where('key', 'like', self::ID.'.%')
            ->orWhere('key', 'like', self::OLD_ID.'.%')
            ->count();

        $this->assertSame(0, $left);
    }

    protected function assertNoTables(): void
    {
        $this->assertFalse($this->schema()->hasTable('extension_manager_tasks'));
        $this->assertFalse($this->schema()->hasTable('package_manager_tasks'));
    }

    /** @return list<string> */
    protected function ran(string $id): array
    {
        return $this->database()->table('migrations')->where('extension', $id)->orderBy('migration')->pluck('migration')->all();
    }

    /** @return array<string, mixed> */
    protected function task(): array
    {
        return ['status' => 'success', 'operation' => 'extension_install', 'output' => '', 'created_at' => '2024-01-01 00:00:00'];
    }

    protected function clean(): void
    {
        $this->schema()->dropIfExists('extension_manager_tasks');
        $this->schema()->dropIfExists('package_manager_tasks');

        $db = $this->database();
        $db->table('migrations')->whereIn('extension', [self::ID, self::OLD_ID])->delete();
        $db->table('settings')->where('key', 'like', self::ID.'.%')->orWhere('key', 'like', self::OLD_ID.'.%')->delete();
    }

    protected function path(): string
    {
        return dirname(__DIR__, 2).'/migrations';
    }

    protected function manager(): Extension
    {
        $root = dirname(__DIR__, 2);
        $extension = new Extension($root, json_decode((string) file_get_contents($root.'/composer.json'), true));
        $this->assertSame(self::ID, $extension->getId());

        return $extension;
    }

    protected function migrator(): Migrator
    {
        return $this->app()->getContainer()->make(Migrator::class);
    }

    protected function schema(): Builder
    {
        return $this->database()->getSchemaBuilder();
    }
}
