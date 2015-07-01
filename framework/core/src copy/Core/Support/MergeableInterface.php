<?php namespace Flarum\Core\Models;

interface MergeableInterface
{
    public function saveAfter(Model $previous);
}
