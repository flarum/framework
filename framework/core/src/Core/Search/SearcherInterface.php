<?php namespace Flarum\Core\Search;

interface SearcherInterface
{
    public function query();

    public function setDefaultSort($defaultSort);
}
