<?php namespace Flarum\Assets;

use Less_Parser;

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

        foreach ($this->strings as $string) {
            $parser->parse($string);
        }

        return $parser->getCss();
    }
}
