<?php namespace Flarum\Assets;

class JsCompiler extends RevisionCompiler
{
    public function format($string)
    {
        return $string.";\n";
    }
}
