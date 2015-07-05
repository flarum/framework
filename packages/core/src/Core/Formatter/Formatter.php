<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Model;

interface Formatter
{
    /**
     * Configure the formatter manager before formatting takes place.
     *
     * @param FormatterManager $manager
     */
    public function config(FormatterManager $manager);

    /**
     * Format the text before purification takes place.
     *
     * @param string $text
     * @param Model|null $model The entity that owns the text.
     * @return string
     */
    public function formatBeforePurification($text, Model $model = null);

    /**
     * Format the text after purification takes place.
     *
     * @param string $text
     * @param Model|null $model The entity that owns the text.
     * @return string
     */
    public function formatAfterPurification($text, Model $model = null);
}
