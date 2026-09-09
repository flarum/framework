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
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
use Flarum\Frontend\AssetManager;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Compiler\RevisionCompiler;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Events\Dispatcher;
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
        $this->info('Clearing the cache...');

        $succeeded = $this->cache->flush();

        if (! $succeeded) {
            $this->error('Could not clear contents of `storage/cache`. Please adjust file permissions and try again. This can frequently be fixed by clearing cache via the `Tools` dropdown on the Administration Dashboard page.');

            return Command::FAILURE;
        }

        $this->info('  cache store');

        $storagePath = $this->paths->storage;

        foreach (['formatter' => 'formatter classes', 'locale' => 'locale catalogues', 'views' => 'compiled views'] as $dir => $label) {
            $removed = count(array_filter(glob($storagePath.'/'.$dir.'/*') ?: [], 'unlink'));

            $this->info(sprintf('  %s (%d)', $label, $removed));
        }

        $this->events->dispatch(new ClearingCache);

        $this->info('Rebuilding...');

        // Rebuild now, while we're here and the memory limit is usually
        // generous, rather than leaving the work to whichever request happens
        // to arrive next. Each is best-effort: the cache is already cleared, so
        // a failure here must not fail the command — the render path still
        // rebuilds lazily, exactly as it did before any of this was warmed.
        try {
            $this->formatter->warm();

            $this->info('  formatter');
        } catch (\Throwable $e) {
            $this->error('  formatter could not be pre-built: '.$e->getMessage());
        }

        try {
            $this->warmAssets((bool) $this->input->getOption('force'));
        } catch (\Throwable $e) {
            $this->error('  assets could not be pre-built: '.$e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Rebuild every compiled asset set, reporting each locale as it goes.
     *
     * Clearing the cache flags each set as needing a rebuild, and that flag is
     * what makes the next request do the work. Recompiling without clearing it
     * would leave every instance still believing it has a rebuild outstanding,
     * so the first visitor would pay for it regardless — hence the flag is
     * settled here once the set has been rebuilt. The compilers are driven
     * directly rather than through
     * {@see \Flarum\Frontend\RecompileFrontendAssets::recompileIfDirty()} so
     * that each locale can be reported as it finishes.
     *
     * Locales are listed individually because most of the time goes on them: a
     * forum with a dozen locales compiles a bundle per locale per frontend, and
     * without the breakdown a slow rebuild is indistinguishable from a hung one.
     */
    protected function warmAssets(bool $force): void
    {
        $table = (new Table($this->output))
            ->setHeaders([['Rebuilt assets'], ['Frontend', 'Bundle', 'Revision', 'Size', 'Time']])
            ->setStyle((new TableStyle)->setCellHeaderFormat('<info>%s</info>'));

        foreach ($this->assets->all() as $name => $assets) {
            // Batched, so the revisions recorded across every compiler below
            // are stored together rather than one statement each.
            $this->versioner->deferWrites();

            try {
                $assetsDir = $assets->getAssetsDir();

                $this->rows($table, $name, [$assets->makeJs(), $assets->makeCss()], $assetsDir, $force);

                foreach ($this->locales->getLocales() as $locale => $display) {
                    $this->rows(
                        $table,
                        $name.' · '.$locale.' ('.$display.')',
                        [$assets->makeLocaleJs($locale), $assets->makeLocaleCss($locale)],
                        $assetsDir,
                        $force
                    );
                }

                // The chunk compiler owns no single filename — it tracks one
                // revision per split chunk, dozens of them — so there is no one
                // hash to show. Report how many moved instead.
                $this->chunkRow($table, $name, $assets->makeJsDirectory(), $force);
            } finally {
                $this->versioner->flushWrites();
            }

            // Settle the flag the clear just set. Recompiling without this
            // would leave every instance still believing it has a rebuild
            // outstanding, so the first visitor would pay for it anyway.
            $this->settings->delete('assets_dirty.'.$assets->getName());
        }

        $table->render();
    }

    /**
     * Rebuild some compilers and add a row per bundle.
     *
     * A row per bundle rather than per step: a locale compiles both a js and a
     * css bundle, and two revisions side by side in one cell read as a single
     * confusing statement about one file.
     *
     * The revision is a hash of the compiled output, so an unchanged bundle
     * keeps the one it had — showing the move is what distinguishes "rebuilt,
     * same bytes" from "rebuilt, clients will refetch".
     *
     * @param CompilerInterface[] $compilers
     */
    protected function rows(Table $table, string $set, array $compilers, Cloud $assetsDir, bool $force): void
    {
        $before = array_map(
            fn (CompilerInterface $compiler) => $this->versioner->getRevision((string) $compiler->getFilename()),
            $compilers
        );

        $started = microtime(true);

        foreach ($compilers as $compiler) {
            $compiler->commit($force);
        }

        // Timed together, because that is how they were compiled; shown once.
        $elapsed = sprintf('%dms', (microtime(true) - $started) * 1000);

        foreach ($compilers as $i => $compiler) {
            $file = (string) $compiler->getFilename();
            $now = $this->versioner->getRevision($file);

            // A bundle with no sources at all records EMPTY_REVISION and has no
            // file, so say so rather than printing the marker as if it were a
            // hash.
            if ($now === RevisionCompiler::EMPTY_REVISION) {
                $table->addRow([$set, $file, 'nothing to compile', '', $i === 0 ? $elapsed : '']);

                continue;
            }

            if ($before[$i] === $now && ! $force) {
                // Byte-identical to what was already there, so nothing was
                // written and its size is not worth a round trip.
                $table->addRow([$set, $file, $now.' unchanged', '', $i === 0 ? $elapsed : '']);

                continue;
            }

            $table->addRow([
                $set,
                $file,
                $before[$i] === $now
                    // Forced: the bytes were rewritten even though they match.
                    ? $now.' rewritten'
                    : ($before[$i] ?? 'new').' → '.$now,
                $this->size($assetsDir, $file),
                $i === 0 ? $elapsed : '',
            ]);
        }
    }

    protected function chunkRow(Table $table, string $set, CompilerInterface $compiler, bool $force): void
    {
        $before = $this->versioner->allRevisions();

        $started = microtime(true);

        $compiler->commit($force);

        $elapsed = sprintf('%dms', (microtime(true) - $started) * 1000);

        $after = $this->versioner->allRevisions();
        $changed = count(array_diff_assoc($after, $before)) + count(array_diff_key($before, $after));

        $table->addRow([$set, 'split chunks', $changed.' changed', '', $elapsed]);
    }

    protected function size(Cloud $assetsDir, string $file): string
    {
        try {
            return $this->format($assetsDir->size($file));
        } catch (\Throwable $e) {
            // An empty bundle legitimately has no file.
            return '';
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
