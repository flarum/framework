<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\MigrationLedger;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * Undo reverses the migrations an update ran, including the ones that ran
 * before a migrate stopped partway, and an undo that stopped partway can be
 * finished. flarum/framework#5357.
 */
class MigrationLedgerUndoTest extends TestCase
{
    private string $dir;
    private ConnectionInterface $db;
    private Migrator $migrator;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-undo-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/work', 0775, true);
        mkdir($this->dir.'/vendor/flarum/core/migrations', 0775, true);

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db = $capsule->getConnection();
        $this->db->getSchemaBuilder()->create('settings', function ($table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
        $this->db->getSchemaBuilder()->create('migrations', function ($table) {
            $table->string('migration');
            $table->string('extension')->nullable();
        });

        $this->migrator = new Migrator(new DatabaseMigrationRepository($this->db, 'migrations'), $this->db, new Filesystem());

        foreach (['2026_01_01_000001_create_first', '2026_01_01_000002_create_second', '2026_01_01_000003_create_third'] as $name) {
            $table = substr($name, 25);
            file_put_contents($this->dir.'/vendor/flarum/core/migrations/'.$name.'.php', "<?php return [
                'up' => function (\$schema) { \$schema->create('$table', function (\$t) { \$t->increments('id'); }); },
                'down' => function (\$schema) { \$schema->drop('$table'); },
            ];");
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_migrations_that_ran_before_a_migrate_stopped_are_reversed(): void
    {
        $ledger = $this->ledger();
        $ledger->before($this->db);

        // Migration 3 of 3 fails: 1 and 2 stay applied, and after() is never reached.
        $this->up('2026_01_01_000001_create_first');
        $this->up('2026_01_01_000002_create_second');

        $this->assertTrue($this->prepare($ledger), 'the applied migrations were not queued to be reversed');

        // The undo puts the old code back before the reversal runs.
        exec('rm -rf '.escapeshellarg($this->dir.'/vendor/flarum/core/migrations'));
        $ledger->finish($this->db, $this->migrator, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('first'));
        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('second'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());
    }

    public function test_an_undo_that_stopped_partway_finishes_on_the_next_attempt(): void
    {
        $ledger = $this->ledger();
        $ledger->before($this->db);
        $this->up('2026_01_01_000001_create_first');
        $this->up('2026_01_01_000002_create_second');
        $this->up('2026_01_01_000003_create_third');
        $ledger->after($this->db, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');
        $this->assertTrue($this->prepare($ledger));

        // An earlier attempt reversed the last one and stopped before the others.
        $this->db->getSchemaBuilder()->drop('third');
        $this->migrator->getRepository()->delete('2026_01_01_000003_create_third');

        $ledger->finish($this->db, $this->migrator, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('first'));
        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('second'));
        $this->assertFalse($ledger->pending());
    }

    private function ledger(): MigrationLedger
    {
        return new MigrationLedger(EnglishTranslator::make(), $this->dir.'/work');
    }

    private function prepare(MigrationLedger $ledger): bool
    {
        return $ledger->prepare(
            $this->createStub(SettingsRepositoryInterface::class),
            $this->migrator,
            $this->createStub(ExtensionManager::class),
            $this->dir.'/vendor',
            $this->db
        );
    }

    private function up(string $name): void
    {
        $migration = $this->migrator->resolve($this->dir.'/vendor/flarum/core/migrations', $name);
        $migration['up']($this->db->getSchemaBuilder());
        $this->migrator->getRepository()->log($name);
    }
}
