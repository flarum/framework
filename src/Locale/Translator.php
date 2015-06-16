<?php namespace Flarum\Locale;

use Closure;

class Translator
{
    protected $translations;

    protected $plural;

    public function __construct(array $translations, Closure $plural)
    {
        $this->translations = $translations;
        $this->plural = $plural;
    }

    public function plural($count)
    {
        $callback = $this->plural;

        return $callback($count);
    }

    public function translate($key, array $input = [])
    {
        $translation = array_get($this->translations, $key);

        if (is_array($translation) && isset($input['count'])) {
            $translation = $translation[$this->plural($input['count'])];
        }

        if (is_string($translation)) {
            foreach ($input as $k => $v) {
                $translation = str_replace('{'.$k.'}', $v, $translation);
            }

            return $translation;
        } else {
            return $key;
        }
    }
}
