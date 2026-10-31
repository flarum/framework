<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\unit;

use FilesystemIterator;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * flarum/phpstan boots a Flarum test site for Larastan, installing it first if
 * it has to. Installing drops every table in the database it uses, so the
 * analysis must never use the database the environment's DB_* settings point
 * at: for a developer, that may be the one they work in.
 *
 * Each case runs the bootstrap in its own PHP process, as PHPStan does, against
 * a SQLite file standing in for that database. Everything lives in a fresh
 * directory, so nothing here touches a real database.
 */
class AnalysisBootstrapTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/flarum-analysis-'.bin2hex(random_bytes(8));
        mkdir($this->dir);

        // The database the environment points at, with a table of its own.
        $configured = new PDO('sqlite:'.$this->configuredDatabase());
        $configured->exec('CREATE TABLE canary (id INTEGER)');
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->dir);

        parent::tearDown();
    }

    #[Test]
    public function it_leaves_the_configured_database_alone(): void
    {
        $this->assertBooted($this->wait($this->bootstrap()));

        $tables = (new PDO('sqlite:'.$this->configuredDatabase()))
            ->query("SELECT name FROM sqlite_master WHERE type = 'table'")
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(['canary'], $tables, 'The analysis bootstrap installed into the configured database.');
    }

    #[Test]
    public function it_installs_once_for_all_the_workers_and_later_runs(): void
    {
        // PHPStan loads the bootstrap in each parallel worker as well as in the
        // main process, and a later run loads it all over again.
        $workers = [$this->bootstrap(), $this->bootstrap(), $this->bootstrap()];
        $outputs = array_map(fn ($process) => $this->wait($process), $workers);
        $outputs[] = $this->wait($this->bootstrap());

        foreach ($outputs as $output) {
            $this->assertBooted($output);
        }

        $this->assertSame(1, substr_count(implode('', array_column($outputs, 'output')), 'Installation complete'));
    }

    private function configuredDatabase(): string
    {
        return $this->dir.'/configured.sqlite';
    }

    /**
     * Start the bootstrap in a new process, from a project directory of its own.
     *
     * @return array{0: resource, 1: array<int, resource>}
     */
    private function bootstrap(): array
    {
        $vendor = getenv('FLARUM_TEST_VENDOR_PATH') ?: dirname(__DIR__, 5).'/vendor';
        $bootstrap = dirname(__DIR__, 4).'/phpstan/bootstrap.php';

        $env = getenv();

        // Only the stand-in database: nothing about a real one reaches the process.
        foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD', 'DB_PREFIX', 'DB_SCHEMA', 'FLARUM_TEST_TMP_DIR', 'FLARUM_PHPSTAN_TMP_DIR'] as $name) {
            unset($env[$name]);
        }

        $env['DB_DRIVER'] = 'sqlite';
        $env['DB_DATABASE'] = $this->configuredDatabase();
        $env['FLARUM_TEST_TMP_DIR_LOCAL'] = $this->dir.'/tmp';
        $env['FLARUM_TEST_VENDOR_PATH'] = $vendor;

        $code = 'require '.var_export("$vendor/autoload.php", true).'; require '.var_export($bootstrap, true).'; echo PHP_EOL, "Booted", PHP_EOL;';

        $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->dir, $env);

        $this->assertIsResource($process);

        return [$process, $pipes];
    }

    /**
     * @param array{0: resource, 1: array<int, resource>} $started
     * @return array{exit: int, output: string}
     */
    private function wait(array $started): array
    {
        [$process, $pipes] = $started;

        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exit' => proc_close($process), 'output' => $output];
    }

    /**
     * @param array{exit: int, output: string} $result
     */
    private function assertBooted(array $result): void
    {
        $this->assertSame(0, $result['exit'], $result['output']);
        $this->assertStringContainsString('Booted', $result['output']);
    }
}
