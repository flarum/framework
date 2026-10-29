<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\CacheClearReport;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
use Flarum\Frontend\AssetManager;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Compiler\RevisionCompiler;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Cloud;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputOption;

class CacheClearCommand extends AbstractCommand
{
    public function __construct(
        protected Store $cache,
        protected Dispatcher $events,
        protected Paths $paths,
        protected Formatter $formatter,
        protected AssetManager $assets,
        protected LocaleManager $locales,
        protected SettingsRepositoryInterface $settings,
        protected VersionerInterface $versioner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Rewrite every compiled asset, even where the output has not changed'
            )
            ->setDescription('Remove all temporary and generated files');
    }

    protected function fire(): int
    {
        $report = $this->clear((bool) $this->input->getOption('force'));

        if ($report === null) {
            $this->error('Could not clear contents of `storage/cache`. Please adjust file permissions and try again. This can frequently be fixed by clearing cache via the `Tools` dropdown on the Administration Dashboard page.');

            return Command::FAILURE;
        }

        $this->render($report);

        return Command::SUCCESS;
    }

    /**
     * Empty the caches and rebuild what can be rebuilt here, describing what
     * happened.
     *
     * Returns null when the cache store could not be emptied, which is the one
     * failure that leaves nothing worth reporting. Everything after that point
     * is best-effort: the caches are already gone, so a step that cannot be
     * pre-built is recorded and the command still succeeds — the render path
     * rebuilds lazily, exactly as it did before any of this was warmed.
     *
     * Public so the admin panel can clear the cache and show the same detail
     * the console does. A listener is called with each step as it finishes, for
     * a caller streaming them to a browser rather than waiting for the lot.
     */
    public function clear(bool $force = false, ?callable $listener = null): ?CacheClearReport
    {
        if (! $this->cache->flush()) {
            return null;
        }

        $report = new CacheClearReport($listener);

        $report->cleared('cache store');

        foreach (['formatter', 'locale', 'views'] as $dir) {
            $files = glob($this->paths->storage.'/'.$dir.'/*') ?: [];

            $report->cleared($dir, count(array_filter($files, 'unlink')));
        }

        $this->events->dispatch(new ClearingCache);

        // Rebuild now, while we're here and the memory limit is usually
        // generous, rather than leaving the work to whichever request happens
        // to arrive next.
        try {
            $this->formatter->warm();
        } catch (\Throwable $e) {
            $report->failed('formatter', $e->getMessage());
        }

        try {
            $this->warmAssets($report, $force);
        } catch (\Throwable $e) {
            $report->failed('assets', $e->getMessage());
        }

        return $report;
    }

    protected function render(CacheClearReport $report): void
    {
        $this->info('Cleared');

        foreach ($report->getCleared() as $cleared) {
            $this->info($cleared['files'] === null
                ? '  '.$cleared['name']
                : sprintf('  %s (%d)', $cleared['name'], $cleared['files']));
        }

        $table = (new Table($this->output))
            ->setHeaders([['Rebuilt assets'], ['Frontend', 'Bundle', 'Revision', 'Size', 'Time']])
            ->setStyle((new TableStyle)->setCellHeaderFormat('<info>%s</info>'));

        $previous = null;

        foreach ($report->getRebuilt() as $row) {
            $frontend = $row['locale'] === null
                ? $row['frontend']
                : $row['frontend'].' · '.$row['locale'].' ('.$row['localeName'].')';

            $table->addRow([
                $frontend,
                $row['bundle'],
                $this->describe($row),
                $row['bytes'] === null ? '' : $this->format($row['bytes']),
                // Bundles compiled together are timed together, so the time is
                // shown once against the first of them.
                $frontend === $previous ? '' : $row['milliseconds'].'ms',
            ]);

            $previous = $frontend;
        }

        $table->render();

        foreach ($report->getFailures() as $failure) {
            $this->error(sprintf('  %s could not be pre-built: %s', $failure['step'], $failure['message']));
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function describe(array $row): string
    {
        return match ($row['state']) {
            CacheClearReport::EMPTY => 'nothing to compile',
            CacheClearReport::CHUNKS => $row['changed'].' changed',
            CacheClearReport::UNCHANGED => $row['revision'].' unchanged',
            CacheClearReport::REWRITTEN => $row['revision'].' rewritten',
            default => ($row['previousRevision'] ?? 'new').' → '.$row['revision'],
        };
    }

    protected function warmAssets(CacheClearReport $report, bool $force): void
    {
        foreach ($this->assets->all() as $name => $assets) {
            // Batched, so the revisions recorded across every compiler below
            // are stored together rather than one statement each.
            $this->versioner->deferWrites();

            try {
                $assetsDir = $assets->getAssetsDir();

                $this->record($report, $name, null, null, [$assets->makeJs(), $assets->makeCss()], $assetsDir, $force);

                foreach ($this->locales->getLocales() as $locale => $display) {
                    $this->record(
                        $report,
                        $name,
                        $locale,
                        $display,
                        [$assets->makeLocaleJs($locale), $assets->makeLocaleCss($locale)],
                        $assetsDir,
                        $force
                    );
                }

                $this->recordChunks($report, $name, $assets->makeJsDirectory(), $force);
            } finally {
                $this->versioner->flushWrites();
            }

            // Settle the flag the clear just set. Recompiling without this
            // would leave every instance still believing it has a rebuild
            // outstanding, so the first visitor would pay for it anyway.
            $this->settings->delete('assets_dirty.'.$assets->getName());
        }
    }

    /**
     * Rebuild some compilers and record one entry per bundle.
     *
     * Per bundle rather than per step: a locale compiles both a js and a css
     * bundle, and describing them as one thing hides which of them changed.
     *
     * The size is read only where a bundle was actually written. On remote
     * storage that is a round trip, and a bundle whose revision did not move is
     * byte-identical to the one reported the last time it changed.
     *
     * @param CompilerInterface[] $compilers
     */
    protected function record(
        CacheClearReport $report,
        string $frontend,
        ?string $locale,
        ?string $localeName,
        array $compilers,
        Cloud $assetsDir,
        bool $force
    ): void {
        $before = array_map(
            fn (CompilerInterface $compiler) => $this->versioner->getRevision((string) $compiler->getFilename()),
            $compilers
        );

        $started = microtime(true);

        foreach ($compilers as $compiler) {
            $compiler->commit($force);
        }

        $elapsed = microtime(true) - $started;

        foreach ($compilers as $i => $compiler) {
            $file = (string) $compiler->getFilename();
            $now = $this->versioner->getRevision($file);

            // A bundle with no sources at all records EMPTY_REVISION and has no
            // file: there is nothing to size, and nothing to say about a hash.
            if ($now === RevisionCompiler::EMPTY_REVISION) {
                $state = CacheClearReport::EMPTY;
            } elseif ($before[$i] !== $now) {
                $state = CacheClearReport::REBUILT;
            } elseif ($force) {
                $state = CacheClearReport::REWRITTEN;
            } else {
                $state = CacheClearReport::UNCHANGED;
            }

            $report->rebuilt(
                $frontend,
                $locale,
                $localeName,
                $file,
                $state,
                $state === CacheClearReport::EMPTY ? null : $now,
                $before[$i],
                in_array($state, [CacheClearReport::REBUILT, CacheClearReport::REWRITTEN], true)
                    ? $this->bytes($assetsDir, $file)
                    : null,
                $elapsed
            );
        }
    }

    protected function recordChunks(CacheClearReport $report, string $frontend, CompilerInterface $compiler, bool $force): void
    {
        $before = $this->versioner->allRevisions();

        $started = microtime(true);

        $compiler->commit($force);

        $elapsed = microtime(true) - $started;

        $after = $this->versioner->allRevisions();

        $report->rebuiltChunks(
            $frontend,
            count(array_diff_assoc($after, $before)) + count(array_diff_key($before, $after)),
            $elapsed
        );
    }

    protected function bytes(Cloud $assetsDir, string $file): ?int
    {
        try {
            return $assetsDir->size($file);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function format(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? sprintf('%.1fMB', $bytes / 1024 / 1024)
            : sprintf('%dKB', max(1, (int) round($bytes / 1024)));
    }

    protected function reportChunks(string $set, CompilerInterface $compiler, bool $force): void
    {
        $before = $this->versioner->allRevisions();

        $started = microtime(true);

        $compiler->commit($force);

        $elapsed = (microtime(true) - $started) * 1000;

        $after = $this->versioner->allRevisions();
        $changed = count(array_diff_assoc($after, $before)) + count(array_diff_key($before, $after));

        $this->info(sprintf(
            '  %-7s %-18s %5dms  %d changed',
            $set,
            'split chunks',
            $elapsed,
            $changed
        ));
    }
}
