<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

class PrefixedYamlFileLoader extends YamlFileLoader
{
    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        $catalogue = parent::load($resource['file'], $locale, $domain);

        if (! empty($resource['prefix'])) {
            $messages = $catalogue->all($domain);

            $prefixedKeys = array_map(function ($k) use ($resource) {
                return $resource['prefix'].$k;
            }, array_keys($messages));

            $catalogue->replace(array_combine($prefixedKeys, $messages));
        }

        return $catalogue;
    }
}
