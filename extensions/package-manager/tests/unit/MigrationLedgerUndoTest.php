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
            $table->increments('id');
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
        $this->plan('flarum/core');
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

    public function test_a_finished_record_is_kept_and_later_migrations_are_left_alone(): void
    {
        $this->plan('flarum/core');
        $ledger = $this->ledger();
        $ledger->before($this->db);
        $this->up('2026_01_01_000001_create_first');
        $ledger->after($this->db, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        // After the update, an admin enables another extension, which migrates.
        $this->migrator->getRepository()->log('2026_02_01_000000_create_other', 'acme-other');

        $this->assertTrue($this->prepare($ledger));
        $this->assertSame([['migration' => '2026_01_01_000001_create_first', 'extension' => null]], $ledger->ran());
    }

    public function test_a_stopped_migrate_is_worked_out_only_for_what_the_update_changed(): void
    {
        $this->plan('flarum/core', 'acme/flarum-ext-tags');
        $ledger = $this->ledger();
        $ledger->before($this->db);
        $this->up('2026_01_01_000001_create_first');
        $this->migrator->getRepository()->log('2026_01_15_000000_add_column', 'acme-tags');
        // The migrate stopped; then another extension was enabled and migrated.
        $this->migrator->getRepository()->log('2026_02_01_000000_create_other', 'acme-other');

        try {
            $this->prepare($ledger);
        } catch (\Flarum\ExtensionManager\Exception\RefusedException) {
            // acme-tags has no migration file here: refused, as it should be.
        }

        $this->assertSame([
            ['migration' => '2026_01_01_000001_create_first', 'extension' => null],
            ['migration' => '2026_01_15_000000_add_column', 'extension' => 'acme-tags'],
        ], $ledger->ran());
    }

    public function test_a_resumed_update_undoes_deferred_migrations_in_execution_order(): void
    {
        $this->db->table('settings')->insert(['key' => 'counter', 'value' => '2']);
        $dir = $this->dir.'/vendor/flarum/core/migrations';
        file_put_contents($dir.'/2026_02_01_000001_older.php', <<<'PHP'
<?php
return [
    'shouldRun' => fn ($schema): bool => $schema->getConnection()->table('settings')->where('key', 'ready')->exists(),
    'up' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->update(['value' => $schema->getConnection()->raw('CAST(value AS REAL) * 2')]),
    'down' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->update(['value' => $schema->getConnection()->raw('CAST(value AS REAL) / 2')]),
];
PHP);
        file_put_contents($dir.'/2026_02_01_000002_newer.php', <<<'PHP'
<?php
return [
    'up' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->increment('value'),
    'down' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->decrement('value'),
];
PHP);
        file_put_contents($dir.'/2026_02_01_000003_retry.php', <<<'PHP'
<?php
return [
    'up' => function ($schema) {
        if (! $schema->getConnection()->table('settings')->where('key', 'ready')->exists()) {
            throw new \RuntimeException('update stopped');
        }
    },
    'down' => fn () => null,
];
PHP);
        $this->plan('flarum/core');
        $ledger = $this->ledger();
        $ledger->before($this->db);

        try {
            $this->migrator->run($dir);
            $this->fail('The update should stop before recording its final ledger.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('update stopped', $exception->getMessage());
        }
        $this->assertSame(3.0, (float) $this->db->table('settings')->where('key', 'counter')->value('value'));

        $this->db->table('settings')->insert(['key' => 'ready', 'value' => '1']);
        $this->migrator->run($dir);
        $ledger->after($this->db, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');
        $this->assertSame(6.0, (float) $this->db->table('settings')->where('key', 'counter')->value('value'));

        $this->assertTrue($this->prepare($ledger));
        $ledger->finish($this->db, $this->migrator, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        $this->assertSame(2.0, (float) $this->db->table('settings')->where('key', 'counter')->value('value'));
    }

    public function test_undo_includes_updated_packages_migrations_deferred_until_after_the_update(): void
    {
        $this->db->table('settings')->insert(['key' => 'counter', 'value' => '2']);
        $dir = $this->dir.'/vendor/flarum/core/migrations';
        $name = '2026_02_01_000001_deferred';
        file_put_contents($dir.'/'.$name.'.php', <<<'PHP'
<?php
return [
    'shouldRun' => fn ($schema): bool => $schema->getConnection()->table('settings')->where('key', 'ready')->exists(),
    'up' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->update(['value' => $schema->getConnection()->raw('CAST(value AS REAL) * 2')]),
    'down' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'counter')->update(['value' => $schema->getConnection()->raw('CAST(value AS REAL) / 2')]),
];
PHP);
        $this->plan('flarum/core');
        $ledger = $this->ledger();
        $ledger->before($this->db);
        $this->up('2026_01_01_000001_create_first');
        $this->migrator->runMigrationList($dir, [$name]);
        $ledger->after($this->db, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        $this->db->table('settings')->insert(['key' => 'ready', 'value' => '1']);
        $this->migrator->runMigrationList($dir, [$name]);
        $this->migrator->getRepository()->log('2026_03_01_000001_unrelated', 'acme-other');
        $this->assertSame(4.0, (float) $this->db->table('settings')->where('key', 'counter')->value('value'));

        $this->assertTrue($this->prepare($ledger));
        $ledger->finish($this->db, $this->migrator, $this->createStub(ExtensionManager::class), $this->dir.'/vendor');

        $this->assertSame(2.0, (float) $this->db->table('settings')->where('key', 'counter')->value('value'));
        $this->assertSame(['2026_03_01_000001_unrelated'], $this->migrator->getRepository()->getRan('acme-other'));
    }

    private function plan(string ...$packages): void
    {
        file_put_contents($this->dir.'/work/plan.json', json_encode([
            'changes' => array_map(fn ($p) => ['op' => 'replace', 'package' => $p, 'from' => '1.0.0', 'to' => '1.1.0'], $packages),
        ]));
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
