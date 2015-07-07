<?php namespace Flarum\Core\Discussions\Search\Fulltext;

interface Driver
{
    /**
     * Return an array of arrays of post IDs, grouped by discussion ID, which
     * match the given string.
     *
     * @param string $string
     * @return array
     */
    public function match($string);
}
