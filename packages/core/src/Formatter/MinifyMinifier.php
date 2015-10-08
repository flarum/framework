<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use MatthiasMullie\Minify;

class MinifyMinifier extends Minifier
{
    /**
     * {@inheritdoc}
     */
    public function getCacheDifferentiator()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function minify($src)
    {
        $minifier = new Minify\JS($src);

        return $minifier->minify();
    }
}
