<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

class LocaleJsCompiler extends JsCompiler
{
    /**
     * @var array
     */
    protected $translations = [];

    /**
     * {@inheritdoc}
     */
    public function setTranslations(array $translations)
    {
        $this->translations = $translations;
    }

    /**
     * {@inheritdoc}
     */
    protected function save(string $file): bool
    {
        array_unshift($this->content, function () {
            return 'flarum.app.translator.translations='.json_encode($this->translations);
        });

        return parent::save($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheDifferentiator()
    {
        return $this->translations;
    }
}
