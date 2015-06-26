<?php namespace Flarum\Core\Search;

interface SearcherInterface
{
    public function getQuery();

    public function getUser();

    public function setDefaultSort($defaultSort);
}
