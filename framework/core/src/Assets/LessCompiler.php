<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Assets;

use Less_Parser;
use Less_Exception_Parser;

class LessCompiler extends RevisionCompiler
{
    public function compile()
    {
        ini_set('xdebug.max_nesting_level', 200);

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => storage_path().'/less'
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
