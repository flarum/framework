<?php namespace Flarum\Core\Users\Commands;

class ConfirmEmail
{
    /**
     * The email confirmation token.
     *
     * @var string
     */
    public $token;

    /**
     * @param string $token The email confirmation token.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
}
