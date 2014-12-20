<?php namespace Flarum\Core\Search\Tokens;

class AuthorToken extends TokenAbstract
{
    /**
     * The token's regex pattern.
     * @var string
     */
    protected $pattern = 'author:(\d+)';

    public function matches()
    {

    }

    public function action()
    {

    }

    public function serialize()
    {

    }
}
