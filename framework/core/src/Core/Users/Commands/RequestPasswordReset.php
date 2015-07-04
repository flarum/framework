<?php namespace Flarum\Core\Users\Commands;

class RequestPasswordReset
{
    /**
     * The email of the user to request a password reset for.
     *
     * @var string
     */
    public $email;

    /**
     * @param string $email The email of the user to request a password reset for.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }
}
