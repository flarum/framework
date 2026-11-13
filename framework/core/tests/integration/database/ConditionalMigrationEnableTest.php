<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class ConditionalMigrationEnableTest extends TestCase
{
    protected string $path;
    protected array $packages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir().'/flarum-migration-enable-'.bin2hex(random_bytes(6));
        (new Filesystem())->makeDirectory($this->path.'/vendor/composer', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->path);
        parent::tearDown();
    }

    protected function package(string $name, string $migration, array $optional = []): void
    {
        $path = $this->path.'/vendor/acme/'.$name;
        (new Filesystem())->makeDirectory($path.'/migrations', 0777, true);
        file_put_contents($path.'/migrations/2026_01_01_000001_'.$name.'.php', '<?php '.$migration);
        $this->packages[] = [
            'name' => 'acme/'.$name,
            'type' => 'flarum-extension',
            'version' => '1.0.0',
            'install-path' => '../acme/'.$name,
            'extra' => ['flarum-extension' => ['title' => $name, 'optional-dependencies' => $optional]],
        ];
    }

    protected function manager(): ExtensionManager
    {
        $container = $this->app()->getContainer();
        $settings = $container->make(SettingsRepositoryInterface::class);
        $settings->set('extensions_enabled', '[]');
        file_put_contents($this->path.'/vendor/composer/installed.json', json_encode(['packages' => $this->packages]));
        // The normal test manager freezes enabled IDs; exercise production state transitions instead.
        $manager = new ExtensionManager(
            $settings,
            new Paths(['base' => $this->path, 'public' => $this->path, 'storage' => $this->path, 'vendor' => $this->path.'/vendor']),
            $container,
            $container->make(Migrator::class),
            $container->make(Dispatcher::class),
            new Filesystem(),
            $container->make(MaintenanceMode::class),
        );
        $container->instance(ExtensionManager::class, $manager);

        return $manager;
    }

    protected function settings(): SettingsRepositoryInterface
    {
        return $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
    }

    #[Test]
    public function enabling_a_provider_completes_an_enabled_extensions_deferred_integration(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::all(
        \Flarum\Extension\Condition::extensionEnabled('acme-provider'),
        fn (\Illuminate\Database\Schema\Builder $schema): bool =>
            $schema->getConnection()->table('settings')->where('key', 'provider_ready')->value('value') === '1',
    ),
);
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();

        $manager->enable('acme-integration');
        $this->assertNull($this->database()->table('settings')->where('key', 'integration_ready')->value('value'));

        $manager->enable('acme-provider');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
        $this->assertTrue($manager->isEnabled('acme-provider'));
        $this->assertSame(['2026_01_01_000001_integration'], $manager->getMigrator()->getRepository()->getRan('acme-integration'));
    }

    public static function installationOrders(): iterable
    {
        yield 'integration first' => [['integration', 'first', 'second']];
        yield 'integration between providers' => [['first', 'integration', 'second']];
        yield 'integration last' => [['second', 'first', 'integration']];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('installationOrders')]
    public function integrations_wait_for_all_providers_regardless_of_installation_order(array $order): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::extensionsEnabled(['acme-first', 'acme-second']),
);
PHP, ['acme/first', 'acme/second']);
        $this->package('first', "return \\Flarum\\Database\\Migration::addSettings(['first_ready' => '1']);");
        $this->package('second', "return \\Flarum\\Database\\Migration::addSettings(['second_ready' => '1']);");
        $manager = $this->manager();

        foreach ($order as $index => $name) {
            $manager->enable('acme-'.$name);
            $this->assertSame($index === 2 ? '1' : null, $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
        }

        $this->assertEqualsCanonicalizing(['acme-first', 'acme-second', 'acme-integration'], $manager->getEnabled());
    }

    #[Test]
    public function a_failed_integration_does_not_publish_enablement_and_can_be_retried(): void
    {
        $this->package('integration', <<<'PHP'
return [
    'shouldRun' => \Flarum\Extension\Condition::extensionEnabled('acme-provider'),
    'up' => fn () => throw new \RuntimeException('integration failed'),
    'down' => fn () => null,
];
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $manager->enable('acme-integration');

        try {
            $manager->enable('acme-provider');
            $this->fail('A failing integration must fail enablement.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('integration failed', $exception->getMessage());
        }

        $this->assertFalse($manager->isEnabled('acme-provider'));
        $this->assertSame(['acme-integration'], $manager->getEnabled());
        $this->assertSame([], $manager->getMigrator()->getRepository()->getRan('acme-integration'));

        file_put_contents($this->path.'/vendor/acme/integration/migrations/2026_01_01_000001_integration.php', <<<'PHP'
<?php
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::extensionEnabled('acme-provider'),
);
PHP);
        $manager->enable('acme-provider');

        $this->assertTrue($manager->isEnabled('acme-provider'));
        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
    }

    #[Test]
    public function enabling_a_provider_does_not_apply_unrelated_unconditional_updates(): void
    {
        $this->package('integration', "return \\Flarum\\Database\\Migration::addSettings(['integration_ready' => '1']);");
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $manager->enable('acme-integration');
        file_put_contents(
            $this->path.'/vendor/acme/integration/migrations/2026_01_01_000002_update.php',
            "<?php return \\Flarum\\Database\\Migration::addSettings(['unrelated_update' => '1']);"
        );

        $manager->enable('acme-provider');

        $this->assertNull($this->database()->table('settings')->where('key', 'unrelated_update')->value('value'));

        $manager->migrate($manager->getExtension('acme-integration'));

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'unrelated_update')->value('value'));
    }

    #[Test]
    public function setting_conditions_observe_values_written_by_the_providers_migrations(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::all(
        \Flarum\Extension\Condition::extensionEnabled('acme-provider'),
        \Flarum\Extension\Condition::setting('provider_ready', '1'),
        \Flarum\Extension\Condition::setting('show_language_selector', true),
    ),
);
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $manager->enable('acme-integration');

        $manager->enable('acme-provider');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
    }

    #[Test]
    public function container_resolved_settings_match_the_fresh_migration_context(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: fn (\Illuminate\Contracts\Container\Container $container): bool =>
        $container->make(\Flarum\Settings\SettingsRepositoryInterface::class)->get('provider_ready') === '1',
);
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $this->assertNull($this->settings()->get('provider_ready'));
        $manager->enable('acme-integration');

        $manager->enable('acme-provider');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
    }

    #[Test]
    public function disabling_a_provider_applies_migrations_waiting_for_its_absence(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['fallback_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::extensionDisabled('acme-provider'),
);
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $manager->enable('acme-provider');
        $manager->enable('acme-integration');
        $this->assertNull($this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));

        $manager->disable('acme-provider');

        $this->assertFalse($manager->isEnabled('acme-provider'));
        $this->assertSame('1', $this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));
    }

    #[Test]
    public function failed_disable_reconciliation_preserves_enabled_state(): void
    {
        $this->package('integration', <<<'PHP'
return [
    'shouldRun' => \Flarum\Extension\Condition::extensionDisabled('acme-provider'),
    'up' => fn () => throw new \RuntimeException('fallback failed'),
    'down' => fn () => null,
];
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        $manager = $this->manager();
        $manager->enable('acme-provider');
        $manager->enable('acme-integration');

        try {
            $manager->disable('acme-provider');
            $this->fail('A failing fallback migration must fail disablement.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('fallback failed', $exception->getMessage());
        }

        $this->assertTrue($manager->isEnabled('acme-provider'));
        $this->assertTrue($manager->isEnabled('acme-integration'));
        $this->assertSame([], $manager->getMigrator()->getRepository()->getRan('acme-integration'));
    }

    #[Test]
    public function first_enablement_can_use_the_extensions_own_declared_setting_defaults(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::setting('acme.feature', true),
);
PHP);
        file_put_contents($this->path.'/vendor/acme/integration/extend.php', <<<'PHP'
<?php
return [(new \Flarum\Extend\Settings())->default('acme.feature', true)];
PHP);
        $manager = $this->manager();

        $manager->enable('acme-integration');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
    }

    #[Test]
    public function first_enablement_resolves_defaults_inside_conditional_extenders(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::setting('acme.feature', true),
);
PHP);
        file_put_contents($this->path.'/vendor/acme/integration/extend.php', <<<'PHP'
<?php
return [
    (new \Flarum\Extend\Settings())->default('acme.enabled', true),
    (new \Flarum\Extend\Conditional())->whenSetting('acme.enabled', true, fn () => [
        (new \Flarum\Extend\Settings())->default('acme.feature', true),
    ]),
];
PHP);
        $manager = $this->manager();

        $manager->enable('acme-integration');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
    }

    #[Test]
    public function disable_conditions_do_not_keep_defaults_from_the_disabled_extension(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['fallback_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::all(
        \Flarum\Extension\Condition::extensionDisabled('acme-provider'),
        \Flarum\Extension\Condition::setting('acme.feature', true),
    ),
);
PHP, ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        file_put_contents($this->path.'/vendor/acme/provider/extend.php', <<<'PHP'
<?php
return [(new \Flarum\Extend\Settings())->default('acme.feature', true)];
PHP);
        $manager = $this->manager();
        $manager->enable('acme-provider');
        $manager->getExtension('acme-provider')->extend($this->app()->getContainer());
        $manager->enable('acme-integration');

        $manager->disable('acme-provider');

        $this->assertNull($this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));
    }

    #[Test]
    public function migration_conditions_remove_boot_defaults_when_their_schema_predicate_changes(): void
    {
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::createTable('default_marker', fn ($table) => $table->integer('id'));
PHP);
        file_put_contents($this->path.'/vendor/acme/integration/extend.php', <<<'PHP'
<?php
return [(new \Flarum\Extend\Conditional())->when(
    fn (\Illuminate\Database\ConnectionInterface $db): bool => $db->getSchemaBuilder()->hasTable('default_marker'),
    fn () => [(new \Flarum\Extend\Settings())->default('acme.feature', true)],
)];
PHP);
        $manager = $this->manager();
        $manager->enable('acme-integration');
        $manager->getExtension('acme-integration')->extend($this->app()->getContainer());
        $this->assertTrue($this->settings()->get('acme.feature'));

        file_put_contents($this->path.'/vendor/acme/integration/migrations/2026_01_01_000002_drop_marker.php', <<<'PHP'
<?php
return [
    'up' => fn ($schema) => $schema->drop('default_marker'),
    'down' => fn ($schema) => $schema->create('default_marker', fn ($table) => $table->integer('id')),
];
PHP);
        file_put_contents($this->path.'/vendor/acme/integration/migrations/2026_01_01_000003_fallback.php', <<<'PHP'
<?php
return \Flarum\Database\Migration::addSettings(
    ['fallback_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::not(\Flarum\Extension\Condition::setting('acme.feature', true)),
);
PHP);

        $manager->migrate($manager->getExtension('acme-integration'));

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));
    }

    #[Test]
    public function composite_extenders_keep_their_boot_defaults_only_while_their_owner_is_enabled(): void
    {
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        file_put_contents($this->path.'/vendor/acme/provider/extend.php', <<<'PHP'
<?php
return [new class implements \Flarum\Extend\ExtenderInterface {
    public function extend(\Illuminate\Contracts\Container\Container $container, ?\Flarum\Extension\Extension $extension = null): void
    {
        (new \Flarum\Extend\Settings())->default('acme.feature', true)->extend($container, $extension);
    }
}];
PHP);
        $this->package('integration', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['integration_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::setting('acme.feature', true),
);
PHP, ['acme/provider']);
        $this->package('fallback', <<<'PHP'
return \Flarum\Database\Migration::addSettings(
    ['fallback_ready' => '1'],
    shouldRun: \Flarum\Extension\Condition::not(\Flarum\Extension\Condition::setting('acme.feature', true)),
);
PHP, ['acme/provider']);
        $manager = $this->manager();
        $manager->enable('acme-provider');
        $manager->getExtension('acme-provider')->extend($this->app()->getContainer());
        $manager->enable('acme-integration');
        $manager->enable('acme-fallback');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'integration_ready')->value('value'));
        $this->assertNull($this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));

        $manager->disable('acme-provider');

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'fallback_ready')->value('value'));
    }

    public static function harnessConditions(): iterable
    {
        yield 'shared predicate' => ["\\Flarum\\Extension\\Condition::extensionEnabled('acme-provider')"];
        yield 'raw callback' => ["fn (\\Flarum\\Extension\\ExtensionManager \$extensions): bool => \$extensions->isEnabled('acme-provider')"];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('harnessConditions')]
    public function the_standard_harness_prepares_providers_before_their_integrations(string $condition): void
    {
        $this->package('integration', str_replace('HARNESS_CONDITION', $condition, <<<'PHP'
return [
    'shouldRun' => HARNESS_CONDITION,
    'up' => function ($schema) {
        $db = $schema->getConnection();
        if ($db->table('settings')->where('key', 'provider_ready')->value('value') !== '1') {
            throw new \RuntimeException('provider has not migrated');
        }
        $db->table('settings')->insert(['key' => 'harness_integration_ready', 'value' => '1']);
    },
    'down' => fn ($schema) => $schema->getConnection()->table('settings')->where('key', 'harness_integration_ready')->delete(),
];
PHP), ['acme/provider']);
        $this->package('provider', "return \\Flarum\\Database\\Migration::addSettings(['provider_ready' => '1']);");
        file_put_contents($this->path.'/composer.json', json_encode(['name' => 'acme/harness', 'type' => 'project']));
        file_put_contents($this->path.'/vendor/composer/installed.json', json_encode(['packages' => $this->packages]));
        $scope = clone $this->app()->getContainer();
        $scope->instance(\Illuminate\Contracts\Container\Container::class, $scope);
        $scope->instance(Paths::class, new Paths(['base' => $this->path, 'public' => $this->path.'/public', 'storage' => $this->path.'/storage', 'vendor' => $this->path.'/vendor']));
        $scope->forgetInstance(ExtensionManager::class);
        $scope->forgetInstance(Migrator::class);

        (new \Flarum\Testing\integration\Extend\OverrideExtensionManagerForTests(['acme-integration', 'acme-provider']))->extend($scope);

        $this->assertSame('1', $this->database()->table('settings')->where('key', 'harness_integration_ready')->value('value'));
    }
}
