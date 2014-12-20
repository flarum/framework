<?php namespace Flarum\Core\Search\Tokens;

interface TokenInterface
{
    public function getPattern();

    public function matches();

    public function action();

    public function serialize();
}
