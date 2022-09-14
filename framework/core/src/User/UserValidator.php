<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractValidator;

class UserValidator extends AbstractValidator
{
    /**
     * @var User|null
     */
    protected $user;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRules()
    {
        $idSuffix = $this->user ? ','.$this->user->id : '';

        return [
            'username' => [
                'required',
                'regex:/^[a-z0-9_-]+$/i',
                'unique:users,username'.$idSuffix,
                'min:3',
                'max:30'
            ],
            'email' => [
                'required',
                'email:filter',
                'unique:users,email'.$idSuffix
            ],
            'password' => [
                'required',
                'min:8'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessages()
    {
        return [
            'username.regex' => $this->translator->trans('core.api.invalid_username_message')
        ];
    }
}
