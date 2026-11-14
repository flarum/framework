<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\ValidationException;
use Flarum\Frontend\Assets;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Less_Exception_Parser;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class ValidateCustomLess
{
    public function __construct(
        protected Assets $assets,
        protected LocaleManager $locales,
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        protected array $customLessSettings = [],
    ) {
    }

    public function whenSettingsSaving(Saving $event): void
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        // Restrict what features can be used in custom LESS. The two groups
        // below are checked differently, because what counts as legitimate
        // differs between them.
        $configVarKeys = array_intersect(
            array_keys($event->settings),
            array_column($this->customLessSettings, 'key')
        );

        // A setting registered as a LESS config variable (e.g.
        // `theme_primary_color`) is interpolated as a variable value —
        // `@config-primary-color: <value>;` — so a value that closes the
        // declaration can append a directive of its own. These hold colours and
        // have no legitimate use for an import, so any directive is refused
        // outright, before the trial compile. That matters: rejecting during
        // the compile still returns 422 but leaves the value stored, whereas
        // refusing here stops it being persisted at all.
        foreach ($configVarKeys as $key) {
            // `@impor` is matched as well as `@import`, because less.php
            // matches the directive as `@import?` and so parses both the same.
            $this->refuse($event, $key, '/@impor|data-uri\s*\(/i');
        }

        // `custom_less` is a stylesheet, so an `@import url(...)` pulling in a
        // webfont is ordinary and must keep working. Imports are therefore left
        // to LessCompiler::containImports(), which refuses anything outside
        // Flarum's own import directories by throwing — the trial compile below
        // turns that into the same validation error. Matching the directive
        // here instead could only reject every import or none, which is what
        // broke the webfont case.
        //
        // `data-uri(` is still refused: it reads a file without going through
        // the import machinery, so the compiler never sees it.
        if (isset($event->settings['custom_less'])) {
            $this->refuse($event, 'custom_less', '/data-uri\s*\(/i');
        }

        // We haven't saved the settings yet, but we want to trial a full
        // recompile of the CSS to see if this custom LESS will break
        // anything. In order to do that, we will temporarily override the
        // settings repository with the new settings so that the recompile
        // is effective. We will also use a dummy filesystem so that nothing
        // is actually written yet.

        $settings = $this->container->make(SettingsRepositoryInterface::class);

        $this->container->extend(
            SettingsRepositoryInterface::class,
            function ($settings) use ($event) {
                return new OverrideSettingsRepository($settings, $event->settings);
            }
        );

        $assetsDir = $this->assets->getAssetsDir();

        $adapter = new InMemoryFilesystemAdapter();
        $this->assets->setAssetsDir(new FilesystemAdapter(new Filesystem($adapter), $adapter));

        $this->settings->delete('custom_less_error');

        try {
            $this->assets->makeCss()->commit();

            foreach ($this->locales->getLocales() as $locale => $name) {
                $this->assets->makeLocaleCss($locale)->commit();
            }
        } catch (Less_Exception_Parser $e) {
            throw new ValidationException(['custom_less' => $e->getMessage()]);
        }

        if (! empty($this->settings->get('custom_less_error'))) {
            throw new ValidationException(['custom_less' => $this->settings->get('custom_less_error')]);
        }

        $this->assets->setAssetsDir($assetsDir);
        $this->container->instance(SettingsRepositoryInterface::class, $settings);
    }

    /**
     * Refuse a setting whose value uses a LESS feature it may not, so it is
     * never persisted.
     *
     * @throws ValidationException
     */
    protected function refuse(Saving $event, string $key, string $pattern): void
    {
        if (! is_string($event->settings[$key]) || ! preg_match($pattern, $event->settings[$key])) {
            return;
        }

        $translator = $this->container->make(TranslatorInterface::class);

        throw new ValidationException([
            $key => $translator->trans('core.admin.appearance.custom_styles_cannot_use_less_features')
        ]);
    }

    public function whenSettingsSaved(Saved $event): void
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        // Flag rather than delete, so the stylesheets already referenced by
        // served pages keep resolving until their replacements exist. The
        // rebuild happens on the next request, and only rewrites a file whose
        // compiled output actually differs.
        (new RecompileFrontendAssets(
            $this->assets,
            $this->locales,
            $this->container->make('events'),
            $this->settings
        ))->markDirty();
    }

    protected function hasDirtyCustomLessSettings(Saved|Saving $event): bool
    {
        if (empty($this->customLessSettings)) {
            return false;
        }

        $dirtySettings = array_intersect(
            array_keys($event->settings),
            array_map(function ($setting) {
                return $setting['key'];
            }, $this->customLessSettings)
        );

        return ! empty($dirtySettings);
    }
}
