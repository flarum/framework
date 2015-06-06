<?php namespace Flarum\Core\Search\Discussions\Fulltext;

interface DriverInterface
{
    public function match($string);
}
