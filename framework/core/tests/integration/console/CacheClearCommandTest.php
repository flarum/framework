<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Formatter\Formatter;
use Flarum\Frontend\AssetManager;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Cache\Repository;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CacheClearCommandTest extends ConsoleTestCase
{
    /**
     * @return array{output: string, status: int}
     */
    private function runCacheClear(array $arguments = []): array
    {
        $output = new BufferedOutput();
        $status = $this->console()->run(
            new ArrayInput(['command' => 'cache:clear'] + $arguments),
            $output
        );

        return ['output' => trim($output->fetch()), 'status' => $status];
    }

    #[Test]
    public function it_succeeds()
    {
        $result = $this->runCacheClear();

        $this->assertEquals(Command::SUCCESS, $result['status'], $result['output']);
    }

    #[Test]
    public function it_leaves_the_formatter_warm()
    {
        /** @var Repository $cache */
        $cache = $this->app()->getContainer()->make(Repository::class);

        // Force the formatter cache cold, the state a clear would otherwise
        // leave the render path in.
        $this->app()->getContainer()->make(Formatter::class)->flush();
        $this->assertFalse($cache->has('flarum.formatter'), 'Precondition: the formatter cache should be cold.');

        $this->runCacheClear();

        // The command rebuilds the formatter itself, so the next render doesn't
        // have to — keeping the compile off the (memory-tight) web request that
        // would otherwise trigger it.
        $this->assertTrue($cache->has('flarum.formatter'), 'cache:clear should leave the formatter cache warm.');
    }

    /**
     * Clearing the cache marks every asset set dirty, and whichever request
     * arrives next rebuilds them — seconds of compiling, and far worse where
     * the assets live on remote storage and each file is a round trip. The
     * command does that work itself, for the same reason it pre-builds the
     * formatter: on the command line the memory limit is generous and nobody
     * is waiting on a page.
     */
    #[Test]
    public function it_leaves_the_assets_warm()
    {
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);

        $this->runCacheClear();

        // The dirty flag is what makes the next request rebuild. Compiling
        // without clearing it would leave every instance still believing it
        // has work to do, and the first visitor would pay for it anyway.
        foreach (['forum', 'admin', 'common'] as $set) {
            $this->assertNull(
                $settings->get('assets_dirty.'.$set),
                "cache:clear should leave no rebuild outstanding for the $set assets."
            );
        }
    }

    #[Test]
    public function it_records_a_revision_for_the_rebuilt_assets()
    {
        /** @var VersionerInterface $versioner */
        $versioner = $this->app()->getContainer()->make(VersionerInterface::class);

        $this->runCacheClear();

        $revisions = $versioner->allRevisions();

        $this->assertArrayHasKey('forum.js', $revisions);
        $this->assertArrayHasKey('forum.css', $revisions);
        $this->assertArrayHasKey('admin.js', $revisions);
    }

    /**
     * A clear that suddenly takes half a minute on remote storage looks broken
     * unless it says what it is doing. Every locale is listed: a forum with a
     * dozen of them spends most of the rebuild on locale bundles, and "it
     * hung" is otherwise indistinguishable from "it is on locale nine".
     */
    #[Test]
    public function it_reports_each_step_including_every_locale()
    {
        $locales = $this->app()->getContainer()->make(LocaleManager::class);

        $output = $this->runCacheClear()['output'];

        foreach (['forum', 'admin', 'common'] as $set) {
            $this->assertStringContainsString($set, $output, "The $set assets should be reported.");
        }

        foreach (array_keys($locales->getLocales()) as $locale) {
            $this->assertStringContainsString($locale, $output, "Locale $locale should be reported.");
        }
    }

    /**
     * The cache is already cleared by the time the rebuild runs, so a rebuild
     * that fails must not report failure for the clear: the render path still
     * rebuilds lazily, exactly as it did before the command warmed anything.
     */
    #[Test]
    public function a_failed_rebuild_does_not_fail_the_clear()
    {
        $container = $this->app()->getContainer();

        $broken = m::mock(AssetManager::class);
        $broken->shouldReceive('all')->andThrow(new \RuntimeException('compile exploded'));

        $container->instance(AssetManager::class, $broken);
        $container->instance('flarum.assets', $broken);

        $result = $this->runCacheClear();

        $this->assertEquals(Command::SUCCESS, $result['status'], $result['output']);
        $this->assertStringContainsString('compile exploded', $result['output']);
    }
}
