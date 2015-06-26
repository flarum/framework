<?php namespace Flarum\Core\Search;

abstract class GambitAbstract
{
    protected $pattern;

    public function apply($bit, SearcherInterface $searcher)
    {
        if ($matches = $this->match($bit)) {
            list($negate) = array_splice($matches, 1, 1);
            $this->conditions($searcher, $matches, !! $negate);
            return true;
        }
    }

    protected function match($bit)
    {
        if (preg_match('/^(-?)'.$this->pattern.'$/i', $bit, $matches)) {
            return $matches;
        }
    }

    abstract protected function conditions(SearcherInterface $searcher, array $matches, $negate);
}
