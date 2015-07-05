<?php namespace Flarum\Assets;

interface Compiler
{
    public function addFile($file);

    public function addString($string);

    public function getFile();
}
