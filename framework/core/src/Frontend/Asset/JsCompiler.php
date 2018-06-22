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

use Illuminate\Cache\Repository;

class JsCompiler extends RevisionCompiler
{
    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @param string $path
     * @param string $filename
     * @param bool $watch
     * @param Repository $cache
     */
    public function __construct($path, $filename, $watch = false, Repository $cache = null)
    {
        parent::__construct($path, $filename, $watch);

        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function format($string)
    {
        return $string.";\n";
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheDifferentiator()
    {
        return $this->watch;
    }
}
