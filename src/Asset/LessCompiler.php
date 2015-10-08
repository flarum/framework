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

use Flarum\Asset\RevisionCompiler;
use Less_Parser;
use Less_Exception_Parser;

class LessCompiler extends RevisionCompiler
{
    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @param string $path
     * @param string $filename
     * @param string $cachePath
     */
    public function __construct($path, $filename, $cachePath)
    {
        parent::__construct($path, $filename);

        $this->cachePath = $cachePath;
    }

    /**
     * {@inheritdoc}
     */
    public function compile()
    {
        ini_set('xdebug.max_nesting_level', 200);

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => $this->cachePath
        ]);

        foreach ($this->files as $file) {
            $parser->parseFile($file);
        }

        foreach ($this->strings as $callback) {
            try {
                $parser->parse($callback());
            } catch (Less_Exception_Parser $e) {
                // TODO: log an error somewhere?
            }
        }

        return $parser->getCss();
    }
}
