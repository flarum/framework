<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Locale\LocaleManager;

class Translations implements AssetInterface
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var callable
     */
    protected $filter;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;

        $this->filter = function () {
            return false;
        };
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
        $sources->addString(function () use ($locale) {
            $translations = $this->getTranslations($locale);

            return 'flarum.core.app.translator.addTranslations('.json_encode($translations).')';
        });
    }

    private function getTranslations(string $locale)
    {
        $translations = $this->locales->getTranslator()->getCatalogue($locale)->all('messages');

        return array_only(
            $translations,
            array_filter(array_keys($translations), $this->filter)
        );
    }

    /**
     * @return callable
     */
    public function getFilter(): callable
    {
        return $this->filter;
    }

    /**
     * @param callable $filter
     */
    public function setFilter(callable $filter)
    {
        $this->filter = $filter;
    }

    public function js(SourceCollector $sources)
    {
    }

    public function css(SourceCollector $sources)
    {
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
    }
}
