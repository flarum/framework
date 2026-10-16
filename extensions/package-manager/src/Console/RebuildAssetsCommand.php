<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Frontend\AssetManager;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Rebuild every frontend's compiled JavaScript, CSS and translations from the
 * code now installed.
 *
 * 🚨 cache:clear does not do this before Flarum 2.0.0. After 1.17.0 updated
 * itself to 1.18.0, ernestdefoe.online served the 7 October admin bundle and
 * fbsfb a translation bundle built before the update. Every step reported
 * success and the new screen never arrived: its strings were missing and its
 * buttons were not in the JavaScript at all.
 *
 * Its own process, like the formatter repair, so it compiles the NEW code.
 * Each compiler only rewrites a file whose output changed.
 */
class RebuildAssetsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('extension-manager:rebuild-assets')
            ->setDescription('Rebuild the compiled JavaScript, CSS and translations for every frontend.')
            /*
             * For an undo run in-process: this request booted the newer
             * version, so building now would bake it in. Flarum rebuilds a set
             * flagged dirty on the next request, which runs the restored code.
             */
            ->addOption('mark-dirty', null, InputOption::VALUE_NONE, 'Only flag the assets for rebuilding on the next request.');
    }

    protected function fire(): int
    {
        $settings = resolve(SettingsRepositoryInterface::class);
        $locales = array_keys(resolve(LocaleManager::class)->getLocales());
        $markDirty = (bool) $this->input->getOption('mark-dirty');

        /*
         * 🚨 A custom LESS error recorded during the update window is retried,
         * not trusted. Flarum leaves the custom LESS out for as long as the
         * flag is set, and the flag was written by a request that compiled new
         * LESS with old PHP. Cleared here, it is set again by the compile below
         * only if the custom LESS genuinely fails, and then this says so.
         */
        $hadError = (string) $settings->get('custom_less_error', '');

        if (! $markDirty && $hadError !== '') {
            $settings->delete('custom_less_error');
        }

        foreach (resolve(AssetManager::class)->all() as $assets) {
            $dirty = 'assets_dirty.'.$assets->getName();

            if ($markDirty) {
                $settings->set($dirty, 1);
                continue;
            }

            /*
             * One frontend that will not compile must not stop the others, nor
             * an update: Flarum compiles these when they are first requested,
             * and a set nobody loads may never have compiled at all.
             */
            try {
                $assets->makeJs()->commit();
                $assets->makeCss()->commit();

                foreach ($locales as $locale) {
                    $assets->makeLocaleJs($locale)->commit();
                    $assets->makeLocaleCss($locale)->commit();
                }

                $assets->makeJsDirectory()->commit();

                $settings->delete($dirty);
            } catch (\Throwable $e) {
                $settings->set($dirty, 1);
                $this->info('Warning: the '.$assets->getName().' assets could not be rebuilt and will be on its next page load: '
                    .strtok($e->getMessage(), "\n"));
            }
        }

        if ($markDirty) {
            $this->info('Assets flagged; the next request rebuilds them.');

            return 0;
        }

        $error = (string) $settings->get('custom_less_error', '');

        if ($error !== '') {
            $this->info('Warning: the custom LESS does not compile, so the forum is shown without it until it is fixed: '
                .strtok($error, "\n"));
        } elseif ($hadError !== '') {
            $this->info('The custom LESS compiles again and is back in the forum\'s styles.');
        }

        $this->info('Assets rebuilt.');

        return 0;
    }
}
