<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Database;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConditionalMigrationTest extends TestCase
{
    protected string $path;
    protected ConnectionInterface $db;
    protected Migrator $migrator;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/flarum-conditional-migration-'.bin2hex(random_bytes(6));
        (new Filesystem())->makeDirectory($this->path);

        $manager = new Manager();
        $manager->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db = $manager->getConnection();
        $this->db->getSchemaBuilder()->create('migrations', function ($table) {
            $table->increments('id');
            $table->string('migration');
            $table->string('extension')->nullable();
        });

        $this->migrator = new Migrator(new DatabaseMigrationRepository($this->db, 'migrations'), $this->db, new Filesystem());
    }

    protected function tearDown(): void
    {
        $this->db->disconnect();
        (new Filesystem())->deleteDirectory($this->path);

        parent::tearDown();
    }

    protected function migration(string $body, string $name = '2026_01_01_000001_integration'): void
    {
        file_put_contents($this->path.'/'.$name.'.php', '<?php '.$body);
    }

    #[Test]
    public function deferred_migrations_run_once_when_their_prerequisites_become_available(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn ($schema): bool => $schema->hasTable('records'),
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('record_id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);

        $this->migrator->run($this->path);

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());

        $this->db->getSchemaBuilder()->create('records', fn ($table) => $table->increments('id'));
        $this->migrator->run($this->path);
        $this->migrator->run($this->path);

        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertSame(['2026_01_01_000001_integration'], $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function conditions_receive_the_migration_schema_and_container_dependencies(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn (\Illuminate\Database\Schema\Builder $databaseSchema, \Illuminate\Filesystem\Filesystem $files): bool =>
        $databaseSchema->hasTable('migrations') && $files->exists(__FILE__),
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('record_id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);

        $this->migrator->run($this->path);

        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('integration'));
    }

    public static function factories(): iterable
    {
        yield 'create table' => ["createTable('integration', fn (\$table) => \$table->integer('id'))", 'integration', 'id'];
        yield 'create table if absent' => ["createTableIfNotExists('integration', fn (\$table) => \$table->integer('id'))", 'integration', 'id'];
        yield 'rename table' => ["renameTable('records', 'integration')", 'integration', 'old'];
        yield 'add columns' => ["addColumns('records', ['added' => ['integer']])", 'records', 'added'];
        yield 'drop columns' => ["dropColumns('records', ['old' => ['integer']])", 'records', 'old', false];
        yield 'rename column' => ["renameColumn('records', 'old', 'renamed')", 'records', 'renamed'];
        yield 'rename columns' => ["renameColumns('records', ['old' => 'renamed'])", 'records', 'renamed'];
        yield 'add settings' => ["addSettings(['integration.enabled' => '1'])", 'settings', 'integration.enabled'];
        yield 'add permissions' => ["addPermissions(['integration.use' => 3])", 'group_permission', 'integration.use'];
    }

    #[Test]
    #[DataProvider('factories')]
    public function every_factory_can_defer_and_later_apply_its_migration(string $factory, string $table, string $column, bool $exists = true): void
    {
        $schema = $this->db->getSchemaBuilder();
        $schema->create('records', function ($table) {
            $table->increments('id');
            $table->integer('old');
        });
        $schema->create('groups', fn ($table) => $table->integer('id'));
        $this->db->table('groups')->insert(['id' => 3]);
        $schema->create('settings', function ($table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
        });
        $schema->create('group_permission', function ($table) {
            $table->integer('group_id');
            $table->string('permission');
        });
        $factory = substr($factory, 0, -1).", shouldRun: fn (\\Illuminate\\Database\\Schema\\Builder \$schema): bool => \$schema->hasTable('ready'))";
        $this->migration('return \\Flarum\\Database\\Migration::'.$factory.';');

        $this->migrator->run($this->path);

        $this->assertSame([], $this->migrator->getRepository()->getRan());
        $this->assertFalse($schema->hasTable('integration'));
        $this->assertSame(['id', 'old'], $schema->getColumnListing('records'));
        $this->assertSame(0, $this->db->table('settings')->count());
        $this->assertSame(0, $this->db->table('group_permission')->count());

        $schema->create('ready', fn ($table) => $table->integer('id'));
        $this->migrator->run($this->path);

        $this->assertSame(['2026_01_01_000001_integration'], $this->migrator->getRepository()->getRan());
        $actual = match ($table) {
            'settings' => $this->db->table($table)->where('key', $column)->exists(),
            'group_permission' => $this->db->table($table)->where('permission', $column)->where('group_id', 3)->exists(),
            default => $schema->hasColumn($table, $column),
        };
        $this->assertSame($exists, $actual);
    }

    #[Test]
    public function a_migration_file_is_resolved_once_per_execution(): void
    {
        $this->migration(<<<'PHP'
file_put_contents(__DIR__.'/loads', 'x', FILE_APPEND);
return [
    'shouldRun' => fn (): bool => true,
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);

        $this->migrator->run($this->path);

        $this->assertSame('x', file_get_contents($this->path.'/loads'));
    }

    #[Test]
    public function composed_conditions_receive_the_same_schema_as_the_migration(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => \Flarum\Extension\Condition::all(
        true,
        \Flarum\Extension\Condition::any(false, fn (\Illuminate\Database\Schema\Builder $schema): bool => $schema->hasTable('migrations')),
    ),
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);

        $this->migrator->run($this->path);

        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('integration'));
    }

    #[Test]
    public function skipping_does_not_block_later_migrations_or_hide_rollback(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn ($schema): bool => $schema->hasTable('ready'),
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);
        $this->migration(<<<'PHP'
return \Flarum\Database\Migration::createTable('later', fn ($table) => $table->integer('id'));
PHP, '2026_01_01_000002_later');
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $this->migrator->setOutput($output);
        $this->migrator->run($this->path);

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('later'));
        $this->assertStringContainsString('Skipped:', $output->fetch());

        $this->db->getSchemaBuilder()->create('ready', fn ($table) => $table->integer('id'));
        $this->migrator->run($this->path);
        $this->db->getSchemaBuilder()->drop('ready');
        $this->migrator->reset($this->path);

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('later'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function rollback_reverses_deferred_migrations_in_actual_execution_order(): void
    {
        $schema = $this->db->getSchemaBuilder();
        $schema->create('records', fn ($table) => $table->float('value'));
        $this->db->table('records')->insert(['value' => 2]);
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn ($schema): bool => $schema->hasTable('ready'),
    'up' => fn ($schema) => $schema->getConnection()->table('records')->update(['value' => $schema->getConnection()->raw('value * 2')]),
    'down' => fn ($schema) => $schema->getConnection()->table('records')->update(['value' => $schema->getConnection()->raw('value / 2')]),
];
PHP);
        $this->migration(<<<'PHP'
return [
    'up' => fn ($schema) => $schema->getConnection()->table('records')->increment('value'),
    'down' => fn ($schema) => $schema->getConnection()->table('records')->decrement('value'),
];
PHP, '2026_01_01_000002_later');

        $this->migrator->run($this->path);
        $this->assertSame(3.0, (float) $this->db->table('records')->value('value'));

        $schema->create('ready', fn ($table) => $table->integer('id'));
        $this->migrator->run($this->path);
        $this->assertSame(6.0, (float) $this->db->table('records')->value('value'));

        $this->migrator->reset($this->path);

        $this->assertSame(2.0, (float) $this->db->table('records')->value('value'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function an_exception_in_a_condition_fails_without_recording_or_running_later_migrations(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn () => throw new \RuntimeException('condition failed'),
    'up' => fn ($schema) => $schema->create('integration', fn ($table) => $table->integer('id')),
    'down' => fn ($schema) => $schema->drop('integration'),
];
PHP);
        $this->migration(<<<'PHP'
return \Flarum\Database\Migration::createTable('later', fn ($table) => $table->integer('id'));
PHP, '2026_01_01_000002_later');

        try {
            $this->migrator->run($this->path);
            $this->fail('A throwing condition must fail the migration run.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('condition failed', $exception->getMessage());
        }

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('later'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function installation_conditions_see_the_selected_extensions_before_application_boot(): void
    {
        $this->assertInstallerCondition(<<<'PHP'
\Flarum\Extension\Condition::all(
    \Flarum\Extension\Condition::extensionEnabled('acme-probe'),
    \Flarum\Extension\Condition::extensionDisabled('acme-absent'),
)
PHP);
    }

    #[Test]
    public function installation_callbacks_receive_the_supplied_paths_and_configuration(): void
    {
        $this->assertInstallerCondition(<<<'PHP'
fn (\Flarum\Foundation\Paths $paths, \Flarum\Foundation\Config $config): bool =>
    is_dir($paths->vendor.'/acme/probe') && $config->url()->getHost() === 'localhost'
PHP);
    }

    #[Test]
    public function installation_without_conditions_does_not_evaluate_boot_callbacks(): void
    {
        $this->assertInstallerCondition(null, <<<'PHP'
[(new \Flarum\Extend\Conditional())->when(fn () => throw new \RuntimeException('boot callback evaluated during installation'), fn () => [])]
PHP);
    }

    public static function changedInstallerDefaults(): iterable
    {
        yield 'activated default' => ["\\Flarum\\Extension\\Condition::setting('acme.feature', true)", '1'];
        yield 'removed default' => ["\\Flarum\\Extension\\Condition::not(\\Flarum\\Extension\\Condition::setting('acme.feature', true))", null];
    }

    #[Test]
    #[DataProvider('changedInstallerDefaults')]
    public function installation_conditions_refresh_defaults_after_earlier_migrations(string $condition, ?string $expected): void
    {
        $this->assertInstallerCondition(
            $condition,
            "[(new \\Flarum\\Extend\\Conditional())->whenSetting('acme.ready', ".var_export($expected, true).", fn () => [(new \\Flarum\\Extend\\Settings())->default('acme.feature', true)])]",
            "return \\Flarum\\Database\\Migration::addSettings(['acme.ready' => '1']);"
        );
    }

    protected function assertInstallerCondition(?string $condition, ?string $extenders = null, ?string $earlierMigration = null): void
    {
        $schema = $this->db->getSchemaBuilder();
        $schema->create('settings', function ($table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
        });
        (new Filesystem())->makeDirectory($this->path.'/vendor/composer', 0777, true);
        (new Filesystem())->makeDirectory($this->path.'/vendor/acme/probe/migrations', 0777, true);
        file_put_contents($this->path.'/vendor/composer/installed.json', json_encode(['packages' => [[
            'name' => 'acme/probe',
            'type' => 'flarum-extension',
            'version' => '1.0.0',
            'install-path' => '../acme/probe',
            'extra' => ['flarum-extension' => ['title' => 'Probe']],
        ]]]));
        file_put_contents($this->path.'/vendor/acme/probe/migrations/2026_01_01_000001_probe.php', str_replace(['INSTALL_CONDITION', 'SHOULD_RUN'], [$condition ?? 'true', $condition === null ? '// ' : ''], <<<'PHP'
<?php
return \Flarum\Database\Migration::addSettings(
    ['installation_ready' => '1'],
    SHOULD_RUNshouldRun: INSTALL_CONDITION,
);
PHP));
        if ($extenders !== null) {
            file_put_contents($this->path.'/vendor/acme/probe/extend.php', '<?php return '.$extenders.';');
        }

        if ($earlierMigration !== null) {
            file_put_contents($this->path.'/vendor/acme/probe/migrations/2026_01_01_000000_prepare.php', '<?php '.$earlierMigration);
        }

        $step = new \Flarum\Install\Steps\EnableBundledExtensions(
            $this->db,
            $this->path.'/vendor',
            $this->path.'/assets',
            ['acme-probe'],
            new \Flarum\Foundation\Paths(['base' => $this->path, 'public' => $this->path, 'storage' => $this->path, 'vendor' => $this->path.'/vendor']),
            new \Flarum\Foundation\Config(['url' => 'http://localhost'])
        );

        $step->run();

        $this->assertSame('1', $this->db->table('settings')->where('key', 'installation_ready')->value('value'));
    }

    #[Test]
    public function reconciliation_waits_for_earlier_unconditional_updates(): void
    {
        $this->migration(<<<'PHP'
return \Flarum\Database\Migration::createTable('integration', fn ($table) => $table->integer('id'));
PHP);
        $this->migration(<<<'PHP'
return \Flarum\Database\Migration::addColumns('integration', ['rating' => ['integer']], shouldRun: fn (): bool => true);
PHP, '2026_01_01_000002_rating');

        $this->migrator->run($this->path, conditionalOnly: true);

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertSame([], $this->migrator->getRepository()->getRan());

        $this->migrator->run($this->path);

        $this->assertTrue($this->db->getSchemaBuilder()->hasColumn('integration', 'rating'));
        $this->assertCount(2, $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function installation_conditions_respect_core_setting_defaults(): void
    {
        $this->assertInstallerCondition("\\Flarum\\Extension\\Condition::setting('show_language_selector', true, strict: true)");
    }

    public static function installerDefaultDeclarations(): iterable
    {
        yield 'direct' => ["[(new \\Flarum\\Extend\\Settings())->default('acme.feature', true)]"];
        yield 'conditional' => ["[(new \\Flarum\\Extend\\Conditional())->whenExtensionEnabled('acme-probe', fn () => [(new \\Flarum\\Extend\\Settings())->default('acme.feature', true)])]"];
        yield 'paths and config callback' => ["[(new \\Flarum\\Extend\\Conditional())->when(fn (\\Flarum\\Foundation\\Paths \$paths, \\Flarum\\Foundation\\Config \$config): bool => is_dir(\$paths->vendor.'/acme/probe') && \$config->url()->getHost() === 'localhost', fn () => [(new \\Flarum\\Extend\\Settings())->default('acme.feature', true)])]"];
        yield 'raw conditional callback' => ["[(new \\Flarum\\Extend\\Conditional())->when(fn (\\Flarum\\Extension\\ExtensionManager \$extensions) => \$extensions->isEnabled('acme-probe'), fn () => [(new \\Flarum\\Extend\\Settings())->default('acme.feature', true)])]"];
    }

    #[Test]
    #[DataProvider('installerDefaultDeclarations')]
    public function installation_conditions_use_defaults_declared_by_selected_extensions(string $extenders): void
    {
        $this->assertInstallerCondition("\\Flarum\\Extension\\Condition::setting('acme.feature', true)", $extenders);
    }

    public static function booleanConditions(): iterable
    {
        yield 'handwritten true' => [true, false];
        yield 'handwritten false' => [false, false];
        yield 'helper true' => [true, true];
        yield 'helper false' => [false, true];
    }

    #[Test]
    #[DataProvider('booleanConditions')]
    public function boolean_conditions_work_for_handwritten_and_helper_migrations(bool $condition, bool $helper): void
    {
        $literal = $condition ? 'true' : 'false';
        $body = $helper
            ? "return \\Flarum\\Database\\Migration::createTable('integration', fn (\$table) => \$table->integer('id'), shouldRun: $literal);"
            : "return ['shouldRun' => $literal, 'up' => fn (\$schema) => \$schema->create('integration', fn (\$table) => \$table->integer('id')), 'down' => fn (\$schema) => \$schema->drop('integration')];";
        $this->migration($body);

        $this->migrator->run($this->path);

        $this->assertSame($condition, $this->db->getSchemaBuilder()->hasTable('integration'));
        $this->assertCount($condition ? 1 : 0, $this->migrator->getRepository()->getRan());
    }

    #[Test]
    public function reconciliation_errors_identify_the_extension_and_migration(): void
    {
        $this->migration(<<<'PHP'
return [
    'shouldRun' => fn () => throw new \RuntimeException('condition failed'),
    'up' => fn () => null,
    'down' => fn () => null,
];
PHP);
        $extension = new \Flarum\Extension\Extension($this->path, ['name' => 'acme/integration']);

        try {
            $this->migrator->run($this->path, $extension, conditionalOnly: true);
            $this->fail('A throwing condition must fail reconciliation.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('acme-integration', $exception->getMessage());
            $this->assertStringContainsString('2026_01_01_000001_integration', $exception->getMessage());
            $this->assertSame('condition failed', $exception->getPrevious()?->getMessage());
        }
    }

    #[Test]
    public function existing_migration_decorators_can_wrap_unconditional_helper_steps(): void
    {
        $this->migration(<<<'PHP'
$migration = \Flarum\Database\Migration::createTable('integration', fn ($table) => $table->integer('id'));
return array_map(fn (callable $step) => fn ($schema) => $step($schema), $migration);
PHP);

        $this->migrator->run($this->path);
        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('integration'));

        $this->migrator->reset($this->path);
        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('integration'));
    }
}
