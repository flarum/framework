<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Symfony\Component\Translation\MessageCatalogueInterface;

class LocaleManager
{
    /**
     * A setting any extension can bump to declare that translations have
     * changed somewhere the registered files cannot show — the database, most
     * obviously. Its value is opaque: only whether it differs matters.
     */
    public const REVISION_STAMP_KEY = 'locale_revision';

    protected array $locales = [];
    protected array $js = [];
    protected array $css = [];

    /**
     * Every translation file registered on this instance, as
     * `<locale>|<prefix>|<file>`.
     *
     * @var list<string>
     */
    protected array $registered = [];

    public function __construct(
        protected Translator $translator,
        protected ?string $cacheDir = null,
        protected ?SettingsRepositoryInterface $settings = null
    ) {
    }

    /**
     * Identifies the translations this instance has been given.
     *
     * A compiled catalogue's filename covers only the fallback locales, and in
     * production `ConfigCache::isFresh()` answers `is_file()` — so once a
     * catalogue exists nothing detects that the translations behind it have
     * changed. Enabling an extension on one instance therefore leaves every
     * other instance serving a catalogue that predates it, indefinitely: no
     * mtime, no TTL and no revision would ever say otherwise, and only deleting
     * the file forces a rebuild.
     *
     * This is derived from what an instance can see for itself — the files it
     * was given, plus a stamp for translations that live elsewhere — so an
     * instance that has been told nothing still works out that its catalogue is
     * stale. Two instances given the same translations arrive at the same
     * value, which is what keeps them from rebuilding in turn forever.
     *
     * Deliberately not derived from the contents or timestamps of those files:
     * that would mean asking each registered resource whether it is fresh,
     * which runs third-party code that has never executed in production — some
     * of it resolving services and querying the database on every request. A
     * file edited in place, with nothing added or removed and no stamp bumped,
     * is the one change this does not see; clearing the cache covers it, which
     * a deployment does anyway.
     */
    public function revision(): string
    {
        $registered = $this->registered;
        sort($registered);

        return hash('xxh128', implode("\n", $registered)."\0".$this->revisionStamp());
    }

    protected function revisionStamp(): string
    {
        return (string) ($this->settings?->get(static::REVISION_STAMP_KEY) ?? '');
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function addLocale(string $locale, string $name): void
    {
        $this->locales[$locale] = $name;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function hasLocale(string $locale): bool
    {
        return isset($this->locales[$locale]);
    }

    public function addTranslations(string $locale, string $file, ?string $module = null): void
    {
        $prefix = $module ? $module.'::' : '';

        // `messages` is the default domain, and we want to support MessageFormat
        // for all translations.
        $domain = 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

        // Recorded so revision() can tell what this instance was given.
        $this->registered[] = $locale.'|'.$prefix.'|'.$file;

        $this->translator->addResource('prefixed_yaml', compact('file', 'prefix'), $locale, $domain);
    }

    public function addJsFile(string $locale, string $js): void
    {
        $this->js[$locale][] = $js;
    }

    public function getJsFiles(string $locale): array
    {
        $files = Arr::get($this->js, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(Arr::get($this->js, $parts[0], []), $files);
        }

        return $files;
    }

    public function addCssFile(string $locale, string $css): void
    {
        $this->css[$locale][] = $css;
    }

    public function getCssFiles(string $locale): array
    {
        $files = Arr::get($this->css, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(Arr::get($this->css, $parts[0], []), $files);
        }

        return $files;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    public function clearCache(): void
    {
        if ($this->cacheDir) {
            array_map('unlink', glob($this->cacheDir.'/*'));
        }
    }
}
