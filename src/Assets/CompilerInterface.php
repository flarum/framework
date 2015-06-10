<?php namespace Flarum\Assets;

interface CompilerInterface
{
    public function addFile($file);

    public function addString($string);

    public function getFile();
}
