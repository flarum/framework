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
    public const REFERENCE_REGEX = '/^=>\s*([a-z0-9_\-\.]+)$/i';

    /**
     * Catalogue objects whose `=> reference` values have already been resolved.
     *
     * Tracked per object rather than per locale: Symfony stores an original
     * catalogue per locale but attaches fresh copies as fallbacks of other
     * locales, so several objects can share one locale name.
     *
     * @var \WeakMap<MessageCatalogueInterface, bool>
     */
    private ?\WeakMap $parsedCatalogues = null;

    public function get($key, array $replace = [], $locale = null): string
    {
        return $this->trans($key, $replace, null, $locale);
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        // Symfony's translator uses ICU MessageFormat, which pluralizes based on arguments.
        return $this->trans($key, $replace, null, $locale);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        if ($locale === null) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        $catalogue = parent::getCatalogue($locale);

        $this->parsedCatalogues ??= new \WeakMap();

        for ($current = $catalogue; $current !== null; $current = $current->getFallbackCatalogue()) {
            if (! isset($this->parsedCatalogues[$current])) {
                $this->parseCatalogue($current);
                $this->parsedCatalogues[$current] = true;
            }
        }

        return $catalogue;
    }

    private function parseCatalogue(MessageCatalogueInterface $catalogue): void
    {
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                if (! empty($translation) && preg_match(self::REFERENCE_REGEX, $translation)) {
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

    /**
     * @param string $locale
     */
    public function setLocale($locale): void
    {
        parent::setLocale($locale);
    }
}
