<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Applier;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Apply\Tree;
use Flarum\ExtensionManager\Host\PhpBinary;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\Fetcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 🚨 Flarum's nightly build already says 2.0.0, and so does the 2.0.0 release
 * it becomes. Moving a forum from one to the other moves flarum/core without
 * moving the version, so nothing puts the forum behind "Update Flarum" and
 * there is nothing to bridge. The bridge used to demand that migrate change
 * the recorded version, and undid every such update as a failure.
 */
class CoreBridgeTest extends TestCase
{
    private string $dir;

    private ?Container $previous;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-bridge-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/project/vendor/flarum/core/src/Foundation', 0775, true);
        mkdir($this->dir.'/work', 0775, true);
        $this->previous = Container::getInstance();
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previous);
        Tree::delete($this->dir);
    }

    public function test_a_core_that_keeps_its_version_is_left_to_the_migrations_step(): void
    {
        $this->forum(recorded: '2.0.0', onDisk: '2.0.0');

        $this->assertStringContainsString('still records 2.0.0', $this->bridge());
    }

    public function test_a_core_with_a_new_version_is_still_bridged(): void
    {
        // Bridging runs `flarum migrate`, which this bare project cannot: the
        // attempt is what proves the new version was not waved through.
        $this->forum(recorded: '2.0.0-rc.8', onDisk: '2.0.0');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('its database could not be');
        $this->bridge();
    }

    private function forum(string $recorded, string $onDisk): void
    {
        file_put_contents(
            $this->dir.'/project/vendor/flarum/core/src/Foundation/Application.php',
            "<?php\n\nnamespace Flarum\\Foundation;\n\nclass Application\n{\n    public const VERSION = '$onDisk';\n}\n"
        );

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db = $capsule->getConnection();
        $db->getSchemaBuilder()->create('settings', function ($table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
        $db->table('settings')->insert(['key' => 'version', 'value' => $recorded]);
        $db->getSchemaBuilder()->create('migrations', function ($table) {
            $table->string('migration');
            $table->string('extension')->nullable();
        });

        $container = new Container();
        $container->instance(ConnectionInterface::class, $db);
        Container::setInstance($container);
    }

    private function bridge(): string
    {
        $journal = new Journal($this->dir.'/work/journal.jsonl');
        $php = new PhpBinary(PHP_BINARY, null, '', null, PHP_BINARY, 'cli', '');

        $steps = new ComposerSteps(
            $this->dir.'/project',
            $this->dir.'/work',
            (new ComposerRunner($this->dir.'/project', __FILE__, $this->dir.'/home'))->withPhp($php),
            new Fetcher($this->dir.'/work/staging'),
            new Applier($this->dir.'/project/vendor', $this->dir.'/work/staging', $this->dir.'/work/trash', $journal),
            $journal,
            vendorPath: $this->dir.'/project/vendor',
        );

        return (new \ReflectionMethod($steps, 'bridgeCore'))->invoke($steps);
    }
}
