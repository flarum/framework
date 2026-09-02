<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator implements TranslatorContract
{
    const REFERENCE_REGEX = '/^=>\s*([a-z0-9_\-\.]+)$/i';

    /**
     * Catalogue objects whose `=> reference` values have already been resolved.
     *
     * Tracked per object rather than per locale: Symfony stores an original
     * catalogue per locale but attaches fresh copies as fallbacks of other
     * locales, so several objects can share one locale name.
     *
     * @var \SplObjectStorage|null
     */
    private $parsedCatalogues;

    public function get($key, array $replace = [], $locale = null)
    {
        return $this->trans($key, $replace, null, $locale);
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        // Symfony's translator uses ICU MessageFormat, which pluralizes based on arguments.
        return $this->trans($key, $replace, null, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        $catalogue = parent::getCatalogue($locale);

        if ($this->parsedCatalogues === null) {
            $this->parsedCatalogues = new \SplObjectStorage();
        }

        for ($current = $catalogue; $current !== null; $current = $current->getFallbackCatalogue()) {
            if (! $this->parsedCatalogues->contains($current)) {
                $this->parseCatalogue($current);
                $this->parsedCatalogues->attach($current);
            }
        }

        return $catalogue;
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     */
    private function parseCatalogue(MessageCatalogueInterface $catalogue)
    {
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                if (! empty($translation) && preg_match(self::REFERENCE_REGEX, $translation, $matches)) {
                    $catalogue->set($id, $this->getTranslation($catalogue, $id, $domain), $domain);
                }
            }
        }
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     * @param string $id
     * @param string $domain
     * @return string
     */
    private function getTranslation(MessageCatalogueInterface $catalogue, $id, $domain)
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
