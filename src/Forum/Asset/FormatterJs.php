<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Asset;

use Flarum\Formatter\Formatter;
use Flarum\Frontend\Asset\AssetInterface;
use Flarum\Frontend\Compiler\Source\SourceCollector;

class FormatterJs implements AssetInterface
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function js(SourceCollector $sources)
    {
        $sources->addString(function () {
            return $this->formatter->getJs();
        });
    }

    public function css(SourceCollector $sources)
    {
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
    }
}
