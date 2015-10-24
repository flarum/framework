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

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null)
    {
        $catalogue = parent::getCatalogue($locale);

        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                $catalogue->set($id, $this->getTranslation($catalogue, $id, $domain), $domain);
            }
        }

        return $catalogue;
    }

    /**
     * @param MessageCatalogueInterface $messages
     * @param string $id
     * @param string $domain
     * @return string
     */
    private function getTranslation(MessageCatalogueInterface $messages, $id, $domain)
    {
        $translation = $messages->get($id, $domain);

        if (preg_match('/^=>\s*([a-z0-9_\-\.]+)$/i', $translation, $matches)) {
            return $this->getTranslation($messages, $matches[1], $domain);
        }

        return $translation;
    }
}
