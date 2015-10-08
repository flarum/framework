<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Asset;

use Exception;
use Illuminate\Cache\Repository;
use MatthiasMullie\Minify;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;

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
        if (! $this->watch) {
            $key = 'js.'.sha1($string);

            $string = $this->cache->rememberForever($key, function () use ($string) {
                return $this->minify($string);
            });
        }

        return $string.";\n";
    }

    /**
     * @inheritDoc
     */
    protected function getCacheDifferentiator()
    {
        return $this->watch;
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minify($source)
    {
        set_time_limit(60);

        try {
            $source = $this->minifyWithClosureCompilerService($source);
        } catch (Exception $e) {
            $source = $this->minifyWithFallback($source);
        }

        return $source;
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minifyWithClosureCompilerService($source)
    {
        $minifier = new ClosureCompilerService;

        $minifier->compilationLevel = 'SIMPLE_OPTIMIZATIONS';
        $minifier->timeout = 60;

        $output = $minifier->minify($source);

        return $output;
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minifyWithFallback($source)
    {
        $minifier = new Minify\JS($source);

        return $minifier->minify();
    }
}
