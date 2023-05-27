<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator implements TranslatorInterface
{
    const REFERENCE_REGEX = '/^=>\s*([a-z0-9_\-\.]+)$/i';

    public function get($key, array $replace = [], $locale = null): string
    {
        return $this->trans($key, $replace, null, $locale);
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        // Symfony's translator uses ICU MessageFormat, which pluralizes based on arguments.
        return $this->trans($key, $replace, null, $locale);
    }

    public function getCatalogue($locale = null): MessageCatalogueInterface
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        $parse = ! isset($this->catalogues[$locale]);

        $catalogue = parent::getCatalogue($locale);

        if ($parse) {
            $this->parseCatalogue($catalogue);

            $fallbackCatalogue = $catalogue;
            while ($fallbackCatalogue = $fallbackCatalogue->getFallbackCatalogue()) {
                $this->parseCatalogue($fallbackCatalogue);
            }
        }

        return $catalogue;
    }

    private function parseCatalogue(MessageCatalogueInterface $catalogue): void
    {
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                if (! empty($translation) && preg_match(self::REFERENCE_REGEX, $translation, $matches)) {
                    $catalogue->set($id, $this->getTranslation($catalogue, $id, $domain), $domain);
                }
            }
        }
    }

    private function getTranslation(MessageCatalogueInterface $catalogue, string $id, string $domain): string
    {
        $translation = $catalogue->get($id, $domain);

        if (preg_match(self::REFERENCE_REGEX, $translation, $matches)) {
            return $this->getTranslation($catalogue, $matches[1], $domain);
        }

        return $translation;
    }

    public function setLocale($locale)
    {
        parent::setLocale($locale);
    }
}
