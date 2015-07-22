<?php namespace Flarum\Assets;

interface Compiler
{
    public function addFile($file);

    public function addString(callable $callback);

    public function getFile();
}
