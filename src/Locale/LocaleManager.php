<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Translation\Translator;

class LocaleManager
{
    /**
     * @var Translator
     */
    protected $translator;

    protected $locales = [];

    protected $js = [];

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    public function addLocale($locale, $name)
    {
        $this->locales[$locale] = $name;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function hasLocale($locale)
    {
        return isset($this->locales[$locale]);
    }

    public function addTranslations($locale, $file)
    {
        $this->translator->addResource('yaml', $file, $locale);
    }

    public function addJsFile($locale, $js)
    {
        $this->js[$locale][] = $js;
    }

    public function getJsFiles($locale)
    {
        $files = array_get($this->js, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(array_get($this->js, $parts[0], []), $files);
        }

        return $files;
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }
}
