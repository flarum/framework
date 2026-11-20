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
use Flarum\ExtensionManager\Host\PhpBinary;
use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Plan\LockDiff;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Testing\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\Fetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Abandoned packages, flarum/framework#3993.
 *
 * Composer says which packages are abandoned, and by whom they are replaced,
 * in the lock it writes: `"abandoned": true`, or the replacement's name.
 * The plan reads that for everything the run would bring in, dependencies
 * included, so the admin is told before anything is downloaded or swapped.
 *
 * And an abandoned package must never be the reason a run fails. Composer
 * 2.9 and later can refuse them while resolving (`audit.block-abandoned`,
 * `policy.abandoned.block`); a forum with that set could not install or
 * update anything that depended on one.
 */
class AbandonedPackagesTest extends TestCase
{
    private string $dir;

    private string|false $composerAuth = false;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/em-abandoned-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/project', 0775, true);
        mkdir($this->dir.'/work', 0775, true);
        mkdir($this->dir.'/home', 0775, true);

        // CI exports COMPOSER_AUTH; a token from the environment must not reach these resolves.
        $this->composerAuth = getenv('COMPOSER_AUTH');
        putenv('COMPOSER_AUTH');
        unset($_SERVER['COMPOSER_AUTH'], $_ENV['COMPOSER_AUTH']);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));

        if ($this->composerAuth !== false) {
            putenv('COMPOSER_AUTH='.$this->composerAuth);
            $_SERVER['COMPOSER_AUTH'] = $this->composerAuth;
        }
    }

    // ── what the plan says ──────────────────────────────────────────────

    public function test_an_install_names_every_abandoned_package_it_brings_in_with_its_replacement(): void
    {
        $this->project();

        $note = $this->steps(['acme/widget'], 'install', inProcess: true)->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertStringContainsString('3 packages will change', $note);
        $this->assertStringContainsString('acme/old-lib is abandoned; its author suggests acme/new-lib instead', $note);
        $this->assertStringContainsString('acme/gone is abandoned, and its author names no replacement', $note);
        $this->assertStringNotContainsString('acme/widget is abandoned', $note);

        $this->assertSame(
            [['package' => 'acme/gone', 'replacement' => null], ['package' => 'acme/old-lib', 'replacement' => 'acme/new-lib']],
            $this->plan()['abandoned']
        );
    }

    /**
     * Only what the run moves. An abandoned package that is already installed
     * and stays as it is was flagged before (core's abandoned-extensions list
     * covers extensions); repeating it on every update of something else
     * would be noise.
     */
    public function test_an_update_names_what_it_moves_and_not_what_it_leaves_alone(): void
    {
        $this->project(installed: ['acme/widget' => '1.0.0', 'acme/old-lib' => '1.0.0', 'acme/gone' => '1.0.0']);

        $this->steps(['acme/widget'], 'update', inProcess: true)->doItem('plan', 'work out what changes', new Run('r1'));

        $plan = $this->plan();
        $this->assertSame(['acme/widget'], array_column($plan['changes'], 'package'));
        $this->assertSame([], $plan['abandoned']);
    }

    public function test_an_update_that_moves_an_abandoned_dependency_names_it(): void
    {
        $this->project(installed: ['acme/widget' => '1.0.0', 'acme/old-lib' => '0.9.0', 'acme/gone' => '1.0.0'], widgetNeeds: '^1.0');

        $note = $this->steps(['acme/widget'], 'update', inProcess: true)->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertContains('acme/old-lib', array_column($this->plan()['changes'], 'package'));
        $this->assertStringContainsString('acme/old-lib is abandoned; its author suggests acme/new-lib instead', $note);
    }

    /** The admin screen reads the list from the run, on every state including a failed one. */
    public function test_the_run_carries_the_list_to_the_screen(): void
    {
        $store = new RunStore(EnglishTranslator::make(), $this->dir.'/runs');
        $store->save(Run::start('r1', time())->failed('stopped', 'plan', time(), 'stopped'));
        mkdir($this->dir.'/runs/r1', 0775, true);
        file_put_contents($this->dir.'/runs/r1/plan.json', json_encode(['changes' => [], 'abandoned' => [
            ['package' => 'acme/old-lib', 'replacement' => 'acme/new-lib'],
            'not a row',
        ]]));

        $this->assertSame([['package' => 'acme/old-lib', 'replacement' => 'acme/new-lib']], $store->present($store->load('r1'))['abandoned']);
    }

    // ── never the reason a run fails ────────────────────────────────────

    /** @return array<string, array{0: bool, 1: array<string,mixed>}> */
    public static function blockingConfigs(): array
    {
        return [
            'audit.block-abandoned, in process' => [true, ['audit' => ['block-abandoned' => true]]],
            'audit.block-abandoned, subprocess' => [false, ['audit' => ['block-abandoned' => true]]],
            'policy.abandoned.block, in process' => [true, ['policy' => ['abandoned' => ['block' => true]]]],
            'policy.abandoned.block, subprocess' => [false, ['policy' => ['abandoned' => ['block' => true]]]],
        ];
    }

    /** @param array<string,mixed> $config */
    #[DataProvider('blockingConfigs')]
    public function test_a_forum_that_blocks_abandoned_packages_can_still_install(bool $inProcess, array $config): void
    {
        $this->project(config: $config);

        $note = $this->steps(['acme/widget'], 'install', $inProcess)->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertStringContainsString('3 packages will change', $note);
        $this->assertCount(2, $this->plan()['abandoned']);
    }

    public function test_composers_own_abandoned_notices_do_not_fail_the_run(): void
    {
        // Composer prints its notices; only its exit code decides failure.
        $this->project();
        $this->steps(['acme/widget'], 'install', inProcess: false)->doItem('plan', 'work out what changes', new Run('r1'));

        $lock = json_decode((string) file_get_contents($this->dir.'/project/composer.lock'), true);
        $this->assertSame('acme/new-lib', $this->find($lock, 'acme/old-lib')['abandoned'] ?? null);
        $this->assertTrue($this->find($lock, 'acme/gone')['abandoned'] ?? null);
    }

    // ── reading the lock ────────────────────────────────────────────────

    /**
     * Every shape `abandoned` can take in a lock. Composer writes `true` or a
     * package name; an empty string or `false` is what a hand-edited or
     * third-party repository might carry, and neither is a replacement.
     */
    public function test_every_shape_of_abandoned_is_read_the_way_composer_means_it(): void
    {
        $lock = ['packages' => [
            ['name' => 'a/named', 'version' => '1.0.0', 'abandoned' => 'b/new'],
            ['name' => 'a/true', 'version' => '1.0.0', 'abandoned' => true],
            ['name' => 'a/empty', 'version' => '1.0.0', 'abandoned' => ''],
            ['name' => 'a/false', 'version' => '1.0.0', 'abandoned' => false],
            ['name' => 'a/fine', 'version' => '1.0.0'],
            ['name' => 'a/self', 'version' => '1.0.0', 'abandoned' => 'A/Self'],
        ], 'packages-dev' => [
            ['name' => 'a/dev', 'version' => '1.0.0', 'abandoned' => 'b/dev-new'],
        ]];

        $changes = array_map(fn (string $name) => new Change(Change::ADD, $name, null, '1.0.0'), ['a/named', 'a/true', 'a/empty', 'a/false', 'a/fine', 'a/self', 'a/dev']);
        $changes[] = new Change(Change::REMOVE, 'a/removed', '1.0.0', null);

        $this->assertSame([
            ['package' => 'a/dev', 'replacement' => 'b/dev-new'],
            ['package' => 'a/empty', 'replacement' => null],
            ['package' => 'a/named', 'replacement' => 'b/new'],
            ['package' => 'a/self', 'replacement' => null],
            ['package' => 'a/true', 'replacement' => null],
        ], (new LockDiff())->abandoned($changes, $lock));
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /**
     * A project whose only source is a local package repository. acme/widget
     * needs acme/old-lib (abandoned for acme/new-lib) and acme/gone (abandoned,
     * no replacement).
     *
     * @param array<string,string> $installed package => version already in the lock
     * @param array<string,mixed> $config
     */
    private function project(array $installed = [], array $config = [], string $widgetNeeds = '1.0.0'): void
    {
        $dist = $this->dir.'/empty.zip';
        $zip = new \ZipArchive();
        $zip->open($dist, \ZipArchive::CREATE);
        $zip->addFromString('README', 'fixture');
        $zip->close();

        $package = fn (string $name, string $version, array $extra = []) => [
            'name' => $name, 'version' => $version, 'dist' => ['type' => 'zip', 'url' => $dist],
        ] + $extra;

        $widget = fn (string $version) => $package('acme/widget', $version, ['require' => ['acme/old-lib' => $widgetNeeds, 'acme/gone' => '*']]);

        $packages = [
            $widget('1.0.0'), $widget('1.1.0'),
            $package('acme/old-lib', '0.9.0', ['abandoned' => 'acme/new-lib']),
            $package('acme/old-lib', '1.0.0', ['abandoned' => 'acme/new-lib']),
            $package('acme/gone', '1.0.0', ['abandoned' => true]),
        ];

        $this->writeJson('project/composer.json', [
            'require' => $installed === [] ? (object) [] : ['acme/widget' => '^1.0'],
            'repositories' => [
                ['type' => 'package', 'package' => $packages],
                ['packagist.org' => false],
            ],
            'config' => ['secure-http' => false] + $config,
        ]);

        $locked = [];

        foreach ($installed as $name => $version) {
            foreach ($packages as $p) {
                if ($p['name'] === $name && $p['version'] === $version) {
                    $locked[] = $p;
                }
            }
        }

        $this->writeJson('project/composer.lock', ['packages' => $locked, 'packages-dev' => []]);
    }

    /** @param list<string> $packages */
    private function steps(array $packages, string $mode, bool $inProcess): ComposerSteps
    {
        $journal = new Journal(EnglishTranslator::make(), $this->dir.'/work/journal.jsonl');

        return new ComposerSteps(
            EnglishTranslator::make(),
            $this->dir.'/project',
            $this->dir.'/work',
            $this->runner($inProcess),
            new Fetcher(EnglishTranslator::make(), new \GuzzleHttp\Client(), $this->dir.'/work/staging'),
            new Applier(EnglishTranslator::make(), $this->dir.'/project/vendor', $this->dir.'/work/staging', $this->dir.'/work/trash', $journal),
            $journal,
            new \GuzzleHttp\Client(),
            $packages,
            $mode
        );
    }

    /** In this process, as on a host without proc_open; or Composer's own binary, in its own process. */
    private function runner(bool $inProcess): ComposerRunner
    {
        $php = new PhpBinary(EnglishTranslator::make(), PHP_BINARY, null, '', null, PHP_BINARY, 'cli', $inProcess ? 'proc_open' : '');
        $composer = dirname(__DIR__, 4).'/vendor/composer/composer/bin/composer';

        return (new ComposerRunner(EnglishTranslator::make(), $this->dir.'/project', $inProcess ? __FILE__ : $composer, $this->dir.'/home'))->withPhp($php);
    }

    /** @return array<string,mixed> */
    private function plan(): array
    {
        return (array) json_decode((string) file_get_contents($this->dir.'/work/plan.json'), true);
    }

    /** @return array<string,mixed> */
    private function find(array $lock, string $name): array
    {
        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $p) {
            if (($p['name'] ?? null) === $name) {
                return $p;
            }
        }

        return [];
    }

    private function writeJson(string $relative, array $data): void
    {
        file_put_contents($this->dir.'/'.$relative, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
