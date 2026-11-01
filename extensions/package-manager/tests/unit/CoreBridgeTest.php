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
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\Fetcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Flarum's nightly build already says 2.0.0, and so does the 2.0.0 release
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

    public function test_an_unreadable_plan_stops_the_run_instead_of_reading_as_empty(): void
    {
        // flarum/framework#5357: a plan.json truncated by a full disk read as an
        // empty plan, and the run finished "done" with nothing applied.
        @mkdir($this->dir.'/work', 0775, true);
        file_put_contents($this->dir.'/work/plan.json', '{"changes":[{"op":"replace","pack');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not be read');
        (new \ReflectionMethod($this->steps(), 'planFile'))->invoke($this->steps());
    }

    private function bridge(): string
    {
        return (new \ReflectionMethod($steps = $this->steps(), 'bridgeCore'))->invoke($steps);
    }

    private function steps(): ComposerSteps
    {
        $journal = new Journal(EnglishTranslator::make(), $this->dir.'/work/journal.jsonl');
        $php = new PhpBinary(EnglishTranslator::make(), PHP_BINARY, null, '', null, PHP_BINARY, 'cli', '');

        $steps = new ComposerSteps(
            EnglishTranslator::make(),
            $this->dir.'/project',
            $this->dir.'/work',
            (new ComposerRunner(EnglishTranslator::make(), $this->dir.'/project', __FILE__, $this->dir.'/home'))->withPhp($php),
            new Fetcher(EnglishTranslator::make(), new \GuzzleHttp\Client(), $this->dir.'/work/staging'),
            new Applier(EnglishTranslator::make(), $this->dir.'/project/vendor', $this->dir.'/work/staging', $this->dir.'/work/trash', $journal),
            $journal,
            new \GuzzleHttp\Client(),
            vendorPath: $this->dir.'/project/vendor',
        );

        return $steps;
    }
}
