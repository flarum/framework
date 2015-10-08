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
use MatthiasMullie\Minify;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;

class JsCompiler extends RevisionCompiler
{
    /**
     * @var bool
     */
    protected $minify;

    /**
     * @param string $path
     * @param string $filename
     * @param bool $minify
     */
    public function __construct($path, $filename, $minify = false)
    {
        parent::__construct($path, $filename);

        $this->minify = $minify;
    }

    /**
     * {@inheritdoc}
     */
    public function format($string)
    {
        return $string.";\n";
    }

    /**
     * @inheritDoc
     */
    public function compile()
    {
        $output = parent::compile();

        if ($this->minify) {
            set_time_limit(60);

            try {
                $output = $this->minifyWithClosureCompilerService($output);
            } catch (Exception $e) {
                $output = $this->minifyWithFallback($output);
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    protected function getCacheDifferentiator()
    {
        return $this->minify;
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
