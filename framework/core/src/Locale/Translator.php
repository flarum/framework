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

use Symfony\Component\Translation\TranslatorInterface;
use Closure;

class Translator implements TranslatorInterface
{
    protected $translations;

    protected $plural;

    public function __construct(array $translations, Closure $plural)
    {
        $this->translations = $translations;
        $this->plural = $plural;
    }

    protected function plural($count)
    {
        $plural = $this->plural;

        return $plural($count);
    }

    public function getLocale()
    {
        //
    }

    public function setLocale($locale)
    {
        //
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $translation = array_get($this->translations, $id);

        if (is_array($translation) && isset($parameters['count'])) {
            $translation = $translation[$this->plural($parameters['count'])];
        }

        if (is_string($translation)) {
            foreach ($parameters as $k => $v) {
                $translation = str_replace('{'.$k.'}', $v, $translation);
            }

            return $translation;
        }

        return $id;
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $parameters['count'] = $number;

        return $this->trans($id, $parameters, $domain, $locale);
    }
}
