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

use Symfony\Component\Translation\Loader\YamlFileLoader as BaseYamlFileLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;

class YamlFileLoader extends BaseYamlFileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $messages = parent::load($resource, $locale, $domain);

        foreach ($messages->all($domain) as $id => $translation) {
            $messages->set($id, $this->getTranslation($messages, $id, $domain));
        }

        return $messages;
    }

    private function getTranslation(MessageCatalogueInterface $messages, $id, $domain)
    {
        $translation = $messages->get($id, $domain);

        if (preg_match('/^=>\s*([a-z0-9_\.]+)$/i', $translation, $matches)) {
            return $this->getTranslation($messages, $matches[1], $domain);
        }

        return $translation;
    }
}