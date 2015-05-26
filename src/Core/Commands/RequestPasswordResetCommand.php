<?php namespace Flarum\Core\Commands;

class RequestPasswordResetCommand
{
    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }
}
