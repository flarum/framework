<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Console;

use Flarum\ExtensionManager\Work\StaleCache;
use Flarum\Console\AbstractCommand;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\Paths;

/**
 * Put Flarum's post formatter back into a state that can render.
 *
 * 🚨 This exists because `flarum cache:clear` reliably breaks post rendering on
 * any forum whose cache driver is not the file store — which is every forum
 * running fof/redis.
 *
 * The formatter is two halves that must agree: a SERIALIZED renderer object
 * cached under `flarum.formatter`, and the generated
 * `storage/formatter/Renderer_<hash>.php` that the object is an instance of.
 * `cache:clear` unlinks that file, and flushes the APPLICATION cache — but core
 * gives the formatter its OWN FileStore, so the entry it needs to remove is in a
 * different store and survives. The entry then names a class whose file is gone,
 * every unserialize yields `__PHP_Incomplete_Class`, and every post render 500s.
 * `rememberForever` means nothing invalidates it on its own.
 *
 * It is not a race. It happens every single time.
 *
 * 🚨 The symptom hides: a discussion LIST renders no post bodies, so `/` and
 * `/all` stay 200 while every discussion, private message and queued mail dies.
 * A site can sit like that for hours looking fine.
 *
 * `Formatter::flush()` forgets the entry through the formatter's own cache — the
 * store that actually holds it — and `warm()` then rebuilds both halves
 * together. Running it after cache:clear closes the hole that command opens.
 */
class RepairFormatterCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('extension-manager:repair-formatter')
            ->setDescription('Rebuild the post formatter so its cached renderer and generated class agree.')
            /*
             * Forget the cached formatter without building a new one. For a
             * rollback run in-process: the request doing it booted the newer
             * version, so the NEXT request — on the restored code — must be
             * the one that builds it.
             */
            ->addOption('flush-only', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Only forget the cached formatter.');
    }

    protected function fire(): int
    {
        // A root-owned cache is moved aside first where it can be (see
        // StaleCache), so the flush below can actually clear it.
        StaleCache::setAside(resolve(Paths::class)->storage);

        $formatter = resolve(Formatter::class);

        $formatter->flush();

        if ($this->input->getOption('flush-only')) {
            $this->info('Formatter forgotten; the next request rebuilds it.');

            return 0;
        }

        $formatter->warm();

        /*
         * 🚨 Then CHECK, because flush() cannot fail loudly.
         *
         * A cache entry written by a process running as root cannot be deleted
         * or overwritten by www-data. flush() unlinks nothing, warm() writes a
         * fresh generated class beside a stale entry that still names the old
         * one, and this command prints "Formatter rebuilt." while every post on
         * the site still 500s. That is exactly the shape of failure this whole
         * extension exists to stop: a repair that reports success and changed
         * nothing.
         *
         * One root-owned file did this to a live forum, and the only clue was
         * that running the repair twice made no difference.
         */
        $stranded = $this->strandedCacheFiles();

        if ($stranded !== []) {
            $this->error(
                'The formatter was rebuilt, but '.count($stranded).' cache file(s) could not be replaced '
                .'because they are not owned by the web server user. Post rendering will still fail. '
                .'Remove them as root and run this again:'
            );

            foreach (array_slice($stranded, 0, 5) as $file) {
                $this->error('  '.$file);
            }

            return 1;
        }

        $this->info('Formatter rebuilt.');

        return 0;
    }

    /**
     * Cache files this process could not have replaced.
     *
     * Ownership rather than is_writable(): a file owned by root with mode 644
     * reports unwritable, which is the same answer, but one owned by root with
     * mode 666 reports writable and still cannot be UNLINKED — removing a file
     * needs write permission on its directory, and the directory is root's too.
     *
     * @return list<string>
     */
    private function strandedCacheFiles(): array
    {
        $me = function_exists('posix_geteuid') ? posix_geteuid() : null;

        if ($me === null) {
            return [];
        }

        $root = resolve(Paths::class)->storage.'/cache';

        if (! is_dir($root)) {
            return [];
        }

        $stranded = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                fn ($item) => ! StaleCache::isSetAside($item->getFilename())
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if (@fileowner($file->getPathname()) !== $me) {
                $stranded[] = $file->getPathname();
            }
        }

        return $stranded;
    }
}
