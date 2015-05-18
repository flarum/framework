<?php namespace Flarum\Core\Search;

abstract class GambitAbstract
{
    protected $pattern;

    public function apply($bit, SearcherInterface $searcher)
    {
        if ($matches = $this->match($bit)) {
            $this->conditions($matches, $searcher);
            return true;
        }
    }

    public function match($bit)
    {
        if (preg_match('/^'.$this->pattern.'$/i', $bit, $matches)) {
            return $matches;
        }
    }
}
