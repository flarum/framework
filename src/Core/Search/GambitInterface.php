<?php namespace Flarum\Core\Search;

interface GambitInterface
{
    public function apply($string, $searcher);
}
