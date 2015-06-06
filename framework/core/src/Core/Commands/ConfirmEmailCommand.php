<?php namespace Flarum\Core\Commands;

class ConfirmEmailCommand
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }
}
