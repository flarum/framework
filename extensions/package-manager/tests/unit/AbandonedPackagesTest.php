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

    /** Left out on purpose: an installed package the run leaves alone was there before it. */
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

    // ── a forum that blocks abandoned packages ──────────────────────────

    /** @return array<string, array{0: bool, 1: array<string,mixed>, 2: string}> */
    public static function blockingConfigs(): array
    {
        $audit = ['audit' => ['block-abandoned' => true]];
        $policy = ['policy' => ['abandoned' => ['block' => true]]];

        return [
            'audit.block-abandoned, in process' => [true, $audit, 'config.audit.ignore-abandoned'],
            'audit.block-abandoned, subprocess' => [false, $audit, 'config.audit.ignore-abandoned'],
            'policy.abandoned.block, in process' => [true, $policy, 'config.policy.abandoned.ignore'],
            'policy.abandoned.block, subprocess' => [false, $policy, 'config.policy.abandoned.ignore'],
        ];
    }

    /** @param array<string,mixed> $config */
    #[DataProvider('blockingConfigs')]
    public function test_a_blocked_resolve_names_the_packages_and_the_setting_that_allows_them(bool $inProcess, array $config, string $setting): void
    {
        $this->assertSame(['acme/old-lib'], $this->blocked($inProcess, $config, $setting));
    }

    /** @return array<string, array{0: array<string,mixed>, 1: array<string,mixed>, 2: string}> */
    public static function oneIgnored(): array
    {
        return [
            'audit.ignore-abandoned' => [
                ['audit' => ['block-abandoned' => true, 'ignore-abandoned' => ['acme/old-lib' => 'test']]],
                ['audit' => ['block-abandoned' => true, 'ignore-abandoned' => ['acme/old-lib' => 'test', 'acme/gone' => 'test']]],
                'config.audit.ignore-abandoned',
            ],
            'policy.abandoned.ignore' => [
                ['policy' => ['abandoned' => ['block' => true, 'ignore' => ['acme/old-lib' => 'test']]]],
                ['policy' => ['abandoned' => ['block' => true, 'ignore' => ['acme/old-lib' => 'test', 'acme/gone' => 'test']]]],
                'config.policy.abandoned.ignore',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $one
     * @param array<string,mixed> $both
     */
    #[DataProvider('oneIgnored')]
    public function test_following_the_advice_allows_only_what_is_listed(array $one, array $both, string $setting): void
    {
        $this->assertSame(['acme/gone'], $this->blocked(true, $one, $setting));

        $this->tearDown();
        $this->setUp();
        $this->project(config: $both);
        $this->steps(['acme/widget'], 'install', inProcess: true)->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertCount(2, $this->plan()['abandoned']);
    }

    public function test_the_setting_named_is_the_one_composer_reads(): void
    {
        $this->assertSame('config.policy.abandoned.ignore', ComposerSteps::abandonedIgnoreKey([]));
        $this->assertSame('config.policy.abandoned.ignore', ComposerSteps::abandonedIgnoreKey(['config' => ['audit' => []]]));
        $this->assertSame('config.audit.ignore-abandoned', ComposerSteps::abandonedIgnoreKey(['config' => ['audit' => ['block-abandoned' => true]]]));
        $this->assertSame('config.policy.abandoned.ignore', ComposerSteps::abandonedIgnoreKey(['config' => ['audit' => ['block-abandoned' => true], 'policy' => ['abandoned' => ['block' => true]]]]));
    }

    /** @param array<string,mixed> $config */
    #[DataProvider('blockingConfigs')]
    public function test_the_block_is_left_to_pick_an_older_version_without_abandoned_dependencies(bool $inProcess, array $config, string $setting): void
    {
        $this->project(config: $config, cleanOldWidget: true);

        $this->steps(['acme/widget'], 'install', $inProcess)->doItem('plan', 'work out what changes', new Run('r1'));

        $this->assertSame(['acme/widget' => '0.5.0'], array_column($this->plan()['changes'], 'to', 'package'));
        $this->assertSame([], $this->plan()['abandoned']);
    }

    public function test_only_abandoned_blocks_are_read_from_composers_output(): void
    {
        $output = <<<'OUT'
              - Root composer.json requires acme/widget * -> satisfiable by acme/widget[1.0.0].
              - acme/widget 1.0.0 requires acme/old-lib 1.0.0 -> found acme/old-lib[1.0.0, 1.1.0] but these were not loaded, because they are abandoned. To turn the feature off entirely, you can set "policy.abandoned.block" to false.
              - acme/other[2.0.0] require acme/x -> found acme/x[1.0.0] but these were not loaded, because they are affected by security advisories ("PKSA-1").
            OUT;

        $this->assertSame(['acme/old-lib'], ComposerSteps::blockedAsAbandoned($output));
        $this->assertSame([], ComposerSteps::blockedAsAbandoned('Your requirements could not be resolved.'));
    }

    // ── reading the lock ────────────────────────────────────────────────

    /**
     * Every shape `abandoned` can take in a lock. Composer writes `true` or a
     * package name; an empty string or `false` is what a hand-edited or
     * third-party repository might carry, and Composer reads neither as
     * abandoned.
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
            ['package' => 'a/named', 'replacement' => 'b/new'],
            ['package' => 'a/self', 'replacement' => null],
            ['package' => 'a/true', 'replacement' => null],
        ], (new LockDiff())->abandoned($changes, $lock));
    }

    /**
     * The same source rule as the installed extension's card
     * (ExtensionManager::extensionFromJson()): a bare `true` on an archive
     * from flarum.org/composer is unreliable and left out; a named
     * replacement counts wherever it comes from.
     */
    public function test_a_bare_flag_from_the_flarum_composer_repository_is_left_out_as_core_leaves_it_out(): void
    {
        $flarum = ['type' => 'zip', 'url' => 'https://flarum.org/composer/dist/acme/premium/1.0.0.zip'];
        $packagist = ['type' => 'zip', 'url' => 'https://api.github.com/repos/acme/free/zipball/abc'];

        $lock = ['packages' => [
            ['name' => 'acme/premium', 'version' => '1.0.0', 'abandoned' => true, 'dist' => $flarum],
            ['name' => 'acme/premium-old', 'version' => '1.0.0', 'abandoned' => 'acme/premium', 'dist' => $flarum],
            ['name' => 'acme/free', 'version' => '1.0.0', 'abandoned' => true, 'dist' => $packagist],
            ['name' => 'acme/odd', 'version' => '1.0.0', 'abandoned' => true, 'dist' => ['url' => ['not', 'a', 'string']]],
        ]];

        $changes = array_map(fn (string $name) => new Change(Change::REPLACE, $name, '0.9.0', '1.0.0'), ['acme/premium', 'acme/premium-old', 'acme/free', 'acme/odd']);

        $this->assertSame([
            ['package' => 'acme/free', 'replacement' => null],
            ['package' => 'acme/odd', 'replacement' => null],
            ['package' => 'acme/premium-old', 'replacement' => 'acme/premium'],
        ], (new LockDiff())->abandoned($changes, $lock));
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /**
     * acme/widget needs acme/old-lib (abandoned for acme/new-lib) and acme/gone
     * (abandoned, no replacement); with $cleanOldWidget, a 0.5.0 needs neither.
     *
     * @param array<string,string> $installed package => version already in the lock
     * @param array<string,mixed> $config
     */
    private function project(array $installed = [], array $config = [], string $widgetNeeds = '1.0.0', bool $cleanOldWidget = false): void
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
            ...($cleanOldWidget ? [$package('acme/widget', '0.5.0')] : []),
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

    /**
     * The packages a blocked install names, checking the sentence names $setting.
     *
     * @param array<string,mixed> $config
     * @return list<string>
     */
    private function blocked(bool $inProcess, array $config, string $setting): array
    {
        $this->project(config: $config);

        try {
            $this->steps(['acme/widget'], 'install', $inProcess)->doItem('plan', 'work out what changes', new Run('r1'));
        } catch (\RuntimeException $e) {
            $message = str_replace("\n", ' ', $e->getMessage());
            $this->assertMatchesRegularExpression('~^This forum\'s Composer settings block abandoned packages, and the install needs .+ To allow (it|them), list (it|them) under "'.preg_quote($setting, '~').'" in composer.json\.~', $message);
            $this->assertStringContainsString('because they are abandoned', $message);
            preg_match('~the install needs (.+?), which~', $message, $m);

            return explode(', ', $m[1]);
        }

        $this->fail('The block was not applied.');
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
        $composer = dirname((new \ReflectionClass(\Composer\Composer::class))->getFileName(), 3).'/bin/composer';

        if (! $inProcess && ! is_file($composer)) {
            $this->markTestSkipped('Composer is not installed in this checkout.');
        }

        return (new ComposerRunner(EnglishTranslator::make(), $this->dir.'/project', $inProcess ? __FILE__ : $composer, $this->dir.'/home'))->withPhp($php);
    }

    /** @return array<string,mixed> */
    private function plan(): array
    {
        return (array) json_decode((string) file_get_contents($this->dir.'/work/plan.json'), true);
    }

    private function writeJson(string $relative, array $data): void
    {
        file_put_contents($this->dir.'/'.$relative, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
