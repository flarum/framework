<?php namespace Flarum\Core\Discussions\Search\Fulltext;

interface DriverInterface
{
    public function match($string);
}
