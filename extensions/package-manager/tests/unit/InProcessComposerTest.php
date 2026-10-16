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
use Flarum\ExtensionManager\Host\Opcache;
use Flarum\ExtensionManager\Host\PhpBinary;
use Flarum\ExtensionManager\Run\NeedsWebRequest;
use Flarum\ExtensionManager\Run\NotYet;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\Fetcher;
use Flarum\ExtensionManager\Work\FreshCode;
use Flarum\ExtensionManager\Work\InProcess;
use Flarum\ExtensionManager\Work\NeedsGit;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Composer inside the request, for hosts where proc_open is disabled.
 *
 * 🚨 The two promises that make it acceptable: the process it runs in is left
 * exactly as it was found, and nothing it does can leave a half-written state —
 * not even when the host kills the request part-way.
 */
class InProcessComposerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-inproc-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/project', 0775, true);
        mkdir($this->dir.'/work', 0775, true);
        mkdir($this->dir.'/home', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->dir);
    }

    // ── runner selection ────────────────────────────────────────────────

    public function test_a_host_without_proc_open_runs_composer_in_process_instead_of_refusing(): void
    {
        $runner = $this->runner('proc_open, exec');

        $this->assertFalse($runner->canSpawn());
        $this->assertTrue($runner->inProcess());
    }

    public function test_a_host_that_can_spawn_still_uses_a_subprocess(): void
    {
        $runner = $this->runner('');

        $this->assertTrue($runner->canSpawn());
        $this->assertFalse($runner->inProcess(), 'in-process is the fallback, never the preference');
    }

    public function test_no_command_line_php_also_falls_back_to_in_process(): void
    {
        $php = new PhpBinary(null, ['/nonexistent/php-for-test'], '', null, '/x/php-fpm', 'fpm-fcgi', '');
        $runner = (new ComposerRunner($this->dir.'/project', __FILE__, $this->dir.'/home'))->withPhp($php);

        $this->assertFalse($runner->canSpawn());
        $this->assertTrue($runner->inProcess());
    }

    // ── state restoration ───────────────────────────────────────────────

    public function test_everything_the_code_inside_changes_is_put_back(): void
    {
        $handler = static fn () => false;
        set_error_handler($handler);
        $exception = static fn () => null;
        set_exception_handler($exception);
        error_reporting(E_ALL & ~E_DEPRECATED);
        $cwd = getcwd();
        putenv('COLUMNS');
        unset($_SERVER['COLUMNS'], $_ENV['COLUMNS']);
        $memory = ini_get('memory_limit');
        $zone = date_default_timezone_get();
        // Set already when the suite runs under `composer test`, by Symfony Console.
        $verbosity = $_SERVER['SHELL_VERBOSITY'] ?? null;

        try {
            $seen = InProcess::isolate(['COMPOSER_HOME' => '/tmp/mw-test-home'], function (array $limits) {
                // Everything Composer and Symfony are known to do to a process.
                set_error_handler(static fn () => true);
                set_error_handler(static fn () => true);
                set_exception_handler(static fn () => null);
                error_reporting(E_ALL);
                chdir(sys_get_temp_dir());
                putenv('COLUMNS=80');
                $_SERVER['SHELL_VERBOSITY'] = 3;
                date_default_timezone_set('Pacific/Chatham');

                return [getenv('COMPOSER_HOME'), $_SERVER['COMPOSER_HOME'] ?? null, $limits];
            });

            $this->assertSame('/tmp/mw-test-home', $seen[0], 'the environment is set for the work');
            $this->assertSame('/tmp/mw-test-home', $seen[1], 'and in $_SERVER, which Composer reads first');
            $this->assertTrue($seen[2]['memory'], 'the CLI lets memory_limit be lifted');

            $this->assertSame($cwd, getcwd());
            $this->assertSame(E_ALL & ~E_DEPRECATED, error_reporting());
            $this->assertSame($handler, $this->currentErrorHandler(), 'Composer\'s handlers are popped, not buried');
            $this->assertSame($exception, $this->currentExceptionHandler());
            $this->assertFalse(getenv('COLUMNS'));
            $this->assertFalse(getenv('COMPOSER_HOME'));
            $this->assertArrayNotHasKey('COMPOSER_HOME', $_SERVER);
            $this->assertSame($verbosity, $_SERVER['SHELL_VERBOSITY'] ?? null);
            $this->assertSame($memory, ini_get('memory_limit'));
            $this->assertSame($zone, date_default_timezone_get());
        } finally {
            restore_error_handler();
            restore_exception_handler();
            error_reporting(E_ALL);
        }
    }

    public function test_state_is_put_back_when_the_code_inside_throws(): void
    {
        $handler = $this->currentErrorHandler();
        $cwd = getcwd();

        try {
            InProcess::isolate([], function () {
                set_error_handler(static fn () => true);
                chdir(sys_get_temp_dir());

                throw new RuntimeException('boom');
            });
            $this->fail('the exception should come out');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame($cwd, getcwd());
        $this->assertSame($handler, $this->currentErrorHandler());
    }

    // ── the VCS-needs-git refusal ───────────────────────────────────────

    public function test_sources_that_need_git_are_named_before_anything_runs(): void
    {
        $versioned = $this->dir.'/project/packages/versioned';
        $unversioned = $this->dir.'/project/packages/unversioned';
        mkdir($versioned, 0775, true);
        mkdir($unversioned, 0775, true);
        file_put_contents($versioned.'/composer.json', json_encode(['name' => 'acme/versioned', 'version' => '1.0.0']));
        file_put_contents($unversioned.'/composer.json', json_encode(['name' => 'acme/unversioned']));

        $this->writeJson('project/composer.json', ['repositories' => [
            ['type' => 'vcs', 'url' => 'https://github.com/acme/private-a'],
            ['type' => 'vcs', 'url' => 'git@github.com:acme/private-b.git', 'no-api' => true],
            ['type' => 'vcs', 'url' => 'https://gitlab.com/acme/public'],
            ['type' => 'vcs', 'url' => 'https://bitbucket.org/acme/public'],
            ['type' => 'vcs', 'url' => 'https://git.example.com/acme/thing.git'],
            ['type' => 'git', 'url' => 'https://github.com/acme/explicit-git'],
            ['type' => 'path', 'url' => 'packages/versioned'],
            ['type' => 'path', 'url' => 'packages/unversioned'],
            ['type' => 'composer', 'url' => 'https://repo.example.com'],
            'packagist.org' => false,
        ]]);

        $blockers = (new NeedsGit($this->dir.'/project', $this->dir.'/home'))->blockers();

        $this->assertSame([
            ['url' => 'https://github.com/acme/private-a', 'why' => NeedsGit::WHY_TOKEN],
            ['url' => 'git@github.com:acme/private-b.git', 'why' => NeedsGit::WHY_GIT],
            ['url' => 'https://git.example.com/acme/thing.git', 'why' => NeedsGit::WHY_GIT],
            ['url' => 'https://github.com/acme/explicit-git', 'why' => NeedsGit::WHY_GIT],
            ['url' => 'packages/unversioned', 'why' => NeedsGit::WHY_GIT],
        ], $blockers);

        $said = NeedsGit::explain($blockers);
        $this->assertStringStartsWith('Nothing was changed.', $said);
        $this->assertStringContainsString('GitHub token', $said);
        $this->assertStringContainsString('https://github.com/acme/private-a', $said);
        $this->assertStringContainsString('Composer repository', $said);
    }

    public function test_a_github_token_anywhere_composer_looks_clears_the_api_case(): void
    {
        $this->writeJson('project/composer.json', ['repositories' => [['type' => 'vcs', 'url' => 'https://github.com/acme/private-a']]]);

        $this->writeJson('home/auth.json', ['github-oauth' => ['github.com' => 'ghp_test']]);
        $this->assertSame([], (new NeedsGit($this->dir.'/project', $this->dir.'/home'))->blockers());

        unlink($this->dir.'/home/auth.json');
        $this->writeJson('project/auth.json', ['github-oauth' => ['github.com' => 'ghp_test']]);
        $this->assertSame([], (new NeedsGit($this->dir.'/project', $this->dir.'/home'))->blockers());
    }

    public function test_planning_refuses_with_a_sentence_and_changes_nothing(): void
    {
        $json = ['require' => [], 'repositories' => [['type' => 'vcs', 'url' => 'https://github.com/acme/private-a']]];
        $this->writeJson('project/composer.json', $json);
        $this->writeJson('project/composer.lock', ['packages' => []]);
        $before = file_get_contents($this->dir.'/project/composer.json');

        try {
            $this->steps(['acme/widget'], 'install')->doItem('plan', 'work out what changes', new Run('r1'));
            $this->fail('it should refuse');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('GitHub token', $e->getMessage());
            $this->assertStringNotContainsString('proc_open', $e->getMessage(), 'a sentence, not a stack trace');
        }

        $this->assertSame($before, file_get_contents($this->dir.'/project/composer.json'));
        $this->assertDirectoryDoesNotExist($this->dir.'/project/vendor');
    }

    // ── a real in-process resolve, no network ───────────────────────────

    public function test_an_in_process_resolve_writes_the_lock_and_never_vendor(): void
    {
        $this->fixtureProject();
        $handler = $this->currentErrorHandler();
        $cwd = getcwd();

        $note = $this->steps(['acme/widget'], 'install')->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertStringContainsString('1 package(s) will change', $note);

        $lock = json_decode((string) file_get_contents($this->dir.'/project/composer.lock'), true);
        $this->assertSame('acme/widget', $lock['packages'][0]['name']);
        $this->assertSame('1.2.0', $lock['packages'][0]['version']);
        $this->assertArrayHasKey('acme/widget', json_decode((string) file_get_contents($this->dir.'/project/composer.json'), true)['require']);

        $this->assertDirectoryDoesNotExist($this->dir.'/project/vendor', 'Composer resolves; the Extension Manager installs');
        $this->assertSame($cwd, getcwd());
        $this->assertSame($handler, $this->currentErrorHandler());
        $this->assertFileExists($this->dir.'/work/composer.lock.before');
        $this->assertFileDoesNotExist($this->dir.'/work/resolve.attempt.json', 'a finished attempt leaves no marker');
    }

    /**
     * 🚨 The IONOS case: the host killed the last attempt mid-resolve. The
     * retry says so, puts the manifests back, and then succeeds.
     */
    public function test_a_resolve_the_host_killed_is_announced_then_retried_from_the_saved_manifests(): void
    {
        $this->fixtureProject();
        $original = file_get_contents($this->dir.'/project/composer.json');
        copy($this->dir.'/project/composer.json', $this->dir.'/work/composer.json.before');
        copy($this->dir.'/project/composer.lock', $this->dir.'/work/composer.lock.before');

        // What the dead request left: composer.json already edited by `require`.
        file_put_contents($this->dir.'/project/composer.json', '{"require": {"half": "written"');
        $this->writeJson('work/resolve.attempt.json', ['attempts' => 1, 'announced' => false, 'stopped' => 'time', 'timeLimit' => 30]);

        $steps = $this->steps(['acme/widget'], 'install');

        try {
            $steps->doItem('plan', 'work out what changes', new Run('r1'));
            $this->fail('the first call should announce the retry');
        } catch (NotYet $e) {
            $this->assertStringContainsString('stopped the request after 30 seconds', $e->getMessage());
            $this->assertStringContainsString('attempt 2 of 6', $e->getMessage());
        }

        $this->assertSame($original, file_get_contents($this->dir.'/project/composer.json'), 'put back before anything else');

        $note = $steps->doItem('plan', 'work out what changes', new Run('r1'));
        $this->assertStringContainsString('1 package(s) will change', $note);
    }

    public function test_after_six_killed_attempts_it_stops_with_the_limit_named(): void
    {
        $this->fixtureProject();
        copy($this->dir.'/project/composer.json', $this->dir.'/work/composer.json.before');
        copy($this->dir.'/project/composer.lock', $this->dir.'/work/composer.lock.before');
        $this->writeJson('work/resolve.attempt.json', ['attempts' => 6, 'announced' => true, 'stopped' => 'time', 'timeLimit' => 30]);

        try {
            $this->steps(['acme/widget'], 'install')->doItem('plan', 'work out what changes', new Run('r1'));
            $this->fail('it should give up');
        } catch (RuntimeException $e) {
            $this->assertNotInstanceOf(NotYet::class, $e);
            $this->assertStringStartsWith('Nothing was changed.', $e->getMessage());
            $this->assertStringContainsString('max_execution_time', $e->getMessage());
        }
    }

    // ── post-swap steps run only on fresh code ──────────────────────────

    public function test_a_long_lived_process_never_runs_post_swap_code_in_process(): void
    {
        $this->expectException(NeedsWebRequest::class);

        (new FreshCode($this->dir.'/work', $this->dir.'/project', $this->opcache(false), 'cli', microtime(true)))->ensure();
    }

    public function test_a_request_that_started_before_the_swap_waits_for_the_next_one(): void
    {
        file_put_contents($this->dir.'/work/journal.jsonl', "{}\n");

        $this->expectException(NotYet::class);
        (new FreshCode($this->dir.'/work', $this->dir.'/project', $this->opcache(false), 'fpm-fcgi', time() - 30))->ensure();
    }

    public function test_opcache_is_cleared_and_the_step_runs_on_the_following_request(): void
    {
        file_put_contents($this->dir.'/work/journal.jsonl', "{}\n");
        touch($this->dir.'/work/journal.jsonl', time() - 60);
        $opcache = $this->opcache(true);

        try {
            (new FreshCode($this->dir.'/work', $this->dir.'/project', $opcache, 'fpm-fcgi', microtime(true)))->ensure();
            $this->fail('the clearing request must not run the step itself');
        } catch (NotYet $e) {
            $this->assertSame(1, $opcache->cleared);
        }

        // The next request began after the clear.
        (new FreshCode($this->dir.'/work', $this->dir.'/project', $opcache, 'fpm-fcgi', microtime(true) + 2))->ensure();
        $this->assertSame(1, $opcache->cleared, 'not cleared again');
    }

    public function test_a_queue_worker_stops_asking_when_only_the_page_can_continue(): void
    {
        $store = new RunStore($this->dir.'/runs');
        $steps = new class implements Steps {
            public function itemsFor(string $phase, Run $run): array
            {
                return $phase === 'plan' ? ['register'] : [];
            }

            public function doItem(string $phase, string $item, Run $run): ?string
            {
                throw new NeedsWebRequest('waiting for the page');
            }
        };

        $runner = new StepRunner($store, $steps, fn () => 1000);
        $runner->begin('r1');
        $runner->step('r1');            // plans
        $run = $runner->step('r1');     // waits

        $this->assertTrue($runner->wasWaiting());
        $this->assertTrue($runner->needsWebRequest());
        $this->assertFalse($run->isFinished());
        $this->assertSame(0, $run->index, 'the item is not skipped');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function runner(string $disabled): ComposerRunner
    {
        $php = new PhpBinary(PHP_BINARY, null, '', null, PHP_BINARY, 'cli', $disabled);

        return (new ComposerRunner($this->dir.'/project', __FILE__, $this->dir.'/home'))->withPhp($php);
    }

    /** @param list<string> $packages */
    private function steps(array $packages, string $mode): ComposerSteps
    {
        $journal = new Journal($this->dir.'/work/journal.jsonl');

        return new ComposerSteps(
            $this->dir.'/project',
            $this->dir.'/work',
            $this->runner('proc_open'),
            new Fetcher($this->dir.'/work/staging'),
            new Applier($this->dir.'/project/vendor', $this->dir.'/work/staging', $this->dir.'/work/trash', $journal),
            $journal,
            $packages,
            $mode
        );
    }

    /** A project whose only source is a local package repository: no network. */
    private function fixtureProject(): void
    {
        $dist = $this->dir.'/widget.zip';
        $zip = new \ZipArchive();
        $zip->open($dist, \ZipArchive::CREATE);
        $zip->addFromString('composer.json', json_encode(['name' => 'acme/widget']));
        $zip->close();

        $package = fn (string $version) => [
            'name' => 'acme/widget',
            'version' => $version,
            'dist' => ['type' => 'zip', 'url' => $dist],
        ];

        $this->writeJson('project/composer.json', [
            'require' => (object) [],
            'repositories' => [
                ['type' => 'package', 'package' => [$package('1.0.0'), $package('1.2.0')]],
                ['packagist.org' => false],
            ],
            'config' => ['secure-http' => false],
        ]);
        $this->writeJson('project/composer.lock', ['packages' => [], 'packages-dev' => []]);
    }

    private function opcache(bool $enabled): Opcache
    {
        return new class($enabled) extends Opcache {
            public int $cleared = 0;

            public function __construct(private bool $enabled)
            {
            }

            public function situation(): array
            {
                return ['state' => $this->enabled ? self::STALE_RISK : self::FINE, 'enabled' => $this->enabled,
                    'validates' => true, 'freq' => 2, 'canReset' => true];
            }

            public function clear(): array
            {
                $this->cleared++;

                return ['done' => true, 'why' => 'cleared'];
            }
        };
    }

    private function writeJson(string $relative, array $data): void
    {
        file_put_contents($this->dir.'/'.$relative, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function currentErrorHandler(): mixed
    {
        $current = set_error_handler(static fn () => false);
        restore_error_handler();

        return $current;
    }

    private function currentExceptionHandler(): mixed
    {
        $current = set_exception_handler(static fn () => null);
        restore_exception_handler();

        return $current;
    }

    private function rmTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->rmTree($path.'/'.$entry);
            }
        }

        @rmdir($path);
    }
}
