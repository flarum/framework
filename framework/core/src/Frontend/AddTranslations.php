<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Closure;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Locale\LocaleManager;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class AddTranslations
{
    public function __construct(
        protected LocaleManager $locales,
        protected ?Closure $filter = null
    ) {
    }

    public function forFrontend(string $name): static
    {
        $this->filter = function (string $id) use ($name) {
            return preg_match('/^.+(?:\.|::)(?:'.$name.'|lib)\./', $id);
        };

        return $this;
    }

    public function to(Assets $assets): void
    {
        $assets->localeJs(function (SourceCollector $sources, string $locale) {
            $sources->addString(function () use ($locale) {
                $translations = $this->getTranslations($locale);

                return 'flarum.core.app.translator.addTranslations('.json_encode($translations).')';
            });
        });
    }

    private function getTranslations(string $locale): array
    {
        $catalogue = $this->locales->getTranslator()->getCatalogue($locale);
        $translations = $catalogue->all('messages');

        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $translations = array_replace($catalogue->all('messages'), $translations);
        }

        return Arr::only(
            $translations,
            array_filter(array_keys($translations), $this->filter)
        );
    }
}
